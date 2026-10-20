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

inspect_image() {
    docker buildx imagetools inspect "$1"
}

create_image_tag() {
    docker buildx imagetools create --tag "$1" "$2"
}

resolve_image_digest() {
    local image_reference=$1
    local error_file
    local inspect_error
    local inspect_output
    local inspect_status

    error_file=$(mktemp)

    if inspect_output=$(inspect_image "$image_reference" 2>"$error_file"); then
        rm -f "$error_file"
        parse_image_digest <<<"$inspect_output"
        return
    else
        inspect_status=$?
    fi

    inspect_error=$(<"$error_file")
    rm -f "$error_file"

    if [[ $inspect_error =~ (^|:)[[:space:]]+(not[[:space:]]found|manifest[[:space:]]unknown)(:|$) ]]; then
        return 3
    fi

    echo "Unable to inspect immutable tag $image_reference:" >&2
    echo "$inspect_error" >&2
    return "$inspect_status"
}

promote_immutable_image() {
    local source_reference=$1
    local immutable_tag=$2
    local expected_digest=$3
    local existing_digest
    local resolve_status

    if existing_digest=$(resolve_image_digest "$immutable_tag"); then
        if [[ $existing_digest != "$expected_digest" ]]; then
            echo "Immutable tag $immutable_tag already points to $existing_digest" >&2
            return 1
        fi

        echo "$immutable_tag already points to the expected digest: $expected_digest"
        return
    else
        resolve_status=$?
    fi

    if [[ $resolve_status -ne 3 ]]; then
        return "$resolve_status"
    fi

    create_image_tag "$immutable_tag" "$source_reference"
}

run_self_test() {
    local creation_log
    local expected_digest=sha256:expected
    local source_reference=example.test/movary@sha256:expected
    local immutable_tag=example.test/movary:1.0.0

    creation_log=$(mktemp)
    trap 'rm -f "$creation_log"' RETURN

    inspect_image() {
        case $TEST_INSPECT_RESULT in
            match)
                printf 'Name: example.test/movary:1.0.0\nDigest: sha256:expected\nManifest: ignored\n'
                ;;
            mismatch)
                printf 'Name: example.test/movary:1.0.0\nDigest: sha256:other\nManifest: ignored\n'
                ;;
            missing)
                echo 'ERROR: example.test/movary:1.0.0: not found' >&2
                return 1
                ;;
            error)
                echo 'ERROR: failed to contact registry: connection timed out' >&2
                return 1
                ;;
        esac
    }

    create_image_tag() {
        printf '%s %s\n' "$1" "$2" >>"$creation_log"
    }

    TEST_INSPECT_RESULT=match promote_immutable_image \
        "$source_reference" "$immutable_tag" "$expected_digest" >/dev/null

    if [[ -s $creation_log ]]; then
        echo 'Expected an existing matching immutable tag not to be recreated' >&2
        return 1
    fi

    if TEST_INSPECT_RESULT=mismatch promote_immutable_image \
        "$source_reference" "$immutable_tag" "$expected_digest" >/dev/null 2>&1; then
        echo 'Expected an immutable tag digest mismatch to fail' >&2
        return 1
    fi

    if [[ -s $creation_log ]]; then
        echo 'Expected a mismatched immutable tag not to be recreated' >&2
        return 1
    fi

    TEST_INSPECT_RESULT=missing promote_immutable_image \
        "$source_reference" "$immutable_tag" "$expected_digest" >/dev/null

    if [[ $(<"$creation_log") != "$immutable_tag $source_reference" ]]; then
        echo 'Expected a definitely missing immutable tag to be created' >&2
        return 1
    fi

    : >"$creation_log"

    if TEST_INSPECT_RESULT=error promote_immutable_image \
        "$source_reference" "$immutable_tag" "$expected_digest" >/dev/null 2>&1; then
        echo 'Expected a registry inspection error to fail closed' >&2
        return 1
    fi

    if [[ -s $creation_log ]]; then
        echo 'Expected a registry inspection error not to create a tag' >&2
        return 1
    fi

    echo 'Immutable image promotion tests passed'
}

if [[ ${1:-} == --self-test ]]; then
    run_self_test
    exit $?
fi

if [[ $# -ne 3 ]]; then
    echo 'Usage: promote-immutable-image.sh <source-reference> <immutable-tag> <expected-digest>' >&2
    exit 1
fi

promote_immutable_image "$1" "$2" "$3"
