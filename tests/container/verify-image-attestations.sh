#!/usr/bin/env bash

set -euo pipefail

check_attestations() {
    local provenance_json=$1
    local sbom_json=$2

    if ! jq --exit-status '
        (keys | sort) == ["linux/amd64", "linux/arm64"]
        and all(.[];
            (.SLSA.buildDefinition.internalParameters.buildConfig | type == "object")
            and (.SLSA.runDetails.builder.id | type == "string" and length > 0)
        )
    ' <<<"$provenance_json" >/dev/null; then
        echo 'Image does not contain maximum SLSA provenance for both runtime platforms' >&2
        return 1
    fi

    if ! jq --exit-status '
        (keys | sort) == ["linux/amd64", "linux/arm64"]
        and all(.[];
            (.SPDX.spdxVersion | type == "string" and startswith("SPDX-"))
            and (.SPDX.packages | type == "array" and length > 0)
        )
    ' <<<"$sbom_json" >/dev/null; then
        echo 'Image does not contain an SPDX SBOM for both runtime platforms' >&2
        return 1
    fi
}

run_self_test() {
    local valid_provenance
    local valid_sbom
    local minimum_provenance
    local missing_arm64_sbom

    valid_provenance=$(jq --null-input --compact-output '{
        "linux/amd64": {
            SLSA: {
                buildDefinition: {internalParameters: {buildConfig: {test: true}}},
                runDetails: {builder: {id: "https://example.test/builder"}}
            }
        },
        "linux/arm64": {
            SLSA: {
                buildDefinition: {internalParameters: {buildConfig: {test: true}}},
                runDetails: {builder: {id: "https://example.test/builder"}}
            }
        }
    }')
    valid_sbom=$(jq --null-input --compact-output '{
        "linux/amd64": {SPDX: {spdxVersion: "SPDX-2.3", packages: [{name: "example"}]}},
        "linux/arm64": {SPDX: {spdxVersion: "SPDX-2.3", packages: [{name: "example"}]}}
    }')
    minimum_provenance=$(jq --null-input --compact-output '{
        "linux/amd64": {
            SLSA: {
                buildDefinition: {},
                runDetails: {builder: {id: "https://example.test/builder"}}
            }
        },
        "linux/arm64": {
            SLSA: {
                buildDefinition: {},
                runDetails: {builder: {id: "https://example.test/builder"}}
            }
        }
    }')
    missing_arm64_sbom=$(jq --null-input --compact-output '{
        "linux/amd64": {SPDX: {spdxVersion: "SPDX-2.3", packages: [{name: "example"}]}}
    }')

    check_attestations "$valid_provenance" "$valid_sbom" >/dev/null

    if check_attestations "$minimum_provenance" "$valid_sbom" >/dev/null 2>&1; then
        echo 'Expected minimum provenance to be rejected' >&2
        return 1
    fi

    if check_attestations "$valid_provenance" "$missing_arm64_sbom" >/dev/null 2>&1; then
        echo 'Expected an incomplete SBOM to be rejected' >&2
        return 1
    fi

    echo 'Image attestation verification tests passed'
}

if [[ ${1:-} == --self-test ]]; then
    run_self_test
    exit $?
fi

image_reference=${1:?Usage: verify-image-attestations.sh <image>}
provenance_json=$(docker buildx imagetools inspect "$image_reference" --format '{{ json .Provenance }}')
sbom_json=$(docker buildx imagetools inspect "$image_reference" --format '{{ json .SBOM }}')

check_attestations "$provenance_json" "$sbom_json"
echo "Image contains maximum SLSA provenance and an SPDX SBOM for both platforms: $image_reference"
