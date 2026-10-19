#!/usr/bin/env bash

set -euo pipefail

image_reference=${1:?Usage: verify-image-index.sh <image>}
index_json=$(docker buildx imagetools inspect "$image_reference" --raw)

if ! jq --exit-status '
    .mediaType == "application/vnd.oci.image.index.v1+json"
    and ([.manifests[] | select(.platform.os == "linux" and .platform.architecture == "amd64")] | length == 1)
    and ([.manifests[] | select(.platform.os == "linux" and .platform.architecture == "arm64")] | length == 1)
    and (
        [.manifests[] | select(
            (.platform.os == "linux" and (.platform.architecture == "amd64" or .platform.architecture == "arm64")) | not
        )] as $additional_manifests
        | [.manifests[] | select(
            .platform.os == "linux" and (.platform.architecture == "amd64" or .platform.architecture == "arm64")
        ) | .digest] as $runtime_digests
        | all($additional_manifests[];
            . as $manifest
            | $manifest.platform.os == "unknown"
            and $manifest.platform.architecture == "unknown"
            and $manifest.annotations["vnd.docker.reference.type"] == "attestation-manifest"
            and ($runtime_digests | index($manifest.annotations["vnd.docker.reference.digest"]) != null)
        )
    )
' <<<"$index_json" >/dev/null; then
    echo "Image index does not contain exactly the expected runtime platforms and associated attestations: $image_reference" >&2
    jq '.manifests[] | {digest, platform, annotations}' <<<"$index_json" >&2
    exit 1
fi

echo "Image index contains the expected amd64 and arm64 manifests: $image_reference"
