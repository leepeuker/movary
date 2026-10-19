#!/usr/bin/env bash

set -euo pipefail

parse_image_digest() {
    awk '
        $1 == "Digest:" && digest == "" { digest = $2 }
        END {
            if (digest == "") {
                exit 1
            }

            sub(/\r$/, "", digest)
            print digest
        }
    '
}

resolve_image_digest() {
    local image_reference=$1

    docker buildx imagetools inspect "$image_reference" | parse_image_digest
}

verify_promoted_image() {
    local image_reference=$1
    local expected_digest=$2
    local attempts=${PROMOTION_VERIFY_ATTEMPTS:-10}
    local delay_seconds=${PROMOTION_VERIFY_DELAY_SECONDS:-3}
    local attempt
    local resolved_digest=unavailable

    if [[ ! $attempts =~ ^[1-9][0-9]*$ ]]; then
        echo "PROMOTION_VERIFY_ATTEMPTS must be a positive integer: $attempts" >&2
        return 1
    fi

    if [[ ! $delay_seconds =~ ^[0-9]+$ ]]; then
        echo "PROMOTION_VERIFY_DELAY_SECONDS must be a non-negative integer: $delay_seconds" >&2
        return 1
    fi

    for ((attempt = 1; attempt <= attempts; attempt++)); do
        if resolved_digest=$(resolve_image_digest "$image_reference" "$attempt" 2>/dev/null) \
            && [[ $resolved_digest == "$expected_digest" ]]; then
            echo "$image_reference resolves to the expected digest: $expected_digest"
            return 0
        fi

        if ((attempt < attempts)); then
            echo "Waiting for $image_reference to resolve to $expected_digest (attempt $attempt/$attempts)" >&2
            sleep "$delay_seconds"
        fi
    done

    if [[ -z $resolved_digest ]]; then
        resolved_digest=unavailable
    fi

    echo "$image_reference resolves to $resolved_digest instead of $expected_digest" >&2
    return 1
}

run_self_test() {
    local failures=0
    local parsed_digest

    produce_inspect_output() {
        local line

        echo 'Digest: sha256:expected'
        for ((line = 0; line < 4096; line++)); do
            echo 'Manifest output that must be consumed to avoid a broken pipe'
        done
    }

    if ! parsed_digest=$(produce_inspect_output | parse_image_digest) \
        || [[ $parsed_digest != sha256:expected ]]; then
        echo 'Expected digest parsing to consume the complete inspect output' >&2
        failures=$((failures + 1))
    fi

    resolve_image_digest() {
        case $2 in
            1)
                return 1
                ;;
            2)
                echo sha256:old
                ;;
            *)
                echo sha256:expected
                ;;
        esac
    }

    if ! PROMOTION_VERIFY_ATTEMPTS=3 PROMOTION_VERIFY_DELAY_SECONDS=0 \
        verify_promoted_image example/image:nightly sha256:expected >/dev/null 2>&1; then
        echo 'Expected digest verification to tolerate delayed registry consistency' >&2
        failures=$((failures + 1))
    fi

    resolve_image_digest() {
        echo sha256:unexpected
    }

    if PROMOTION_VERIFY_ATTEMPTS=2 PROMOTION_VERIFY_DELAY_SECONDS=0 \
        verify_promoted_image example/image:nightly sha256:expected >/dev/null 2>&1; then
        echo 'Expected digest verification to reject a persistent mismatch' >&2
        failures=$((failures + 1))
    fi

    if [[ $failures -ne 0 ]]; then
        return 1
    fi

    echo 'Promoted image verification tests passed'
}

if [[ ${1:-} == --self-test ]]; then
    run_self_test
    exit $?
fi

if [[ $# -ne 2 ]]; then
    echo 'Usage: verify-promoted-image.sh <image-reference> <expected-digest>' >&2
    exit 1
fi

verify_promoted_image "$1" "$2"
