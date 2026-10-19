#!/usr/bin/env bash

set -euo pipefail

stable_semver_pattern='^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)$'

decimal_is_greater() {
    local left=$1
    local right=$2

    if ((${#left} != ${#right})); then
        ((${#left} > ${#right}))
        return
    fi

    [[ $left > $right ]]
}

semver_is_greater() {
    local left=$1
    local right=$2
    local left_major left_minor left_patch
    local right_major right_minor right_patch

    IFS='.' read -r left_major left_minor left_patch <<<"$left"
    IFS='.' read -r right_major right_minor right_patch <<<"$right"

    if [[ $left_major != "$right_major" ]]; then
        decimal_is_greater "$left_major" "$right_major"
        return
    fi

    if [[ $left_minor != "$right_minor" ]]; then
        decimal_is_greater "$left_minor" "$right_minor"
        return
    fi

    decimal_is_greater "$left_patch" "$right_patch"
}

select_release_aliases() {
    local release_tag=$1
    local release_major release_minor release_patch
    local published_tag published_major published_minor published_patch
    local publish_major=true
    local publish_minor=true
    local publish_latest=true

    if [[ ! $release_tag =~ $stable_semver_pattern ]]; then
        echo "Release alias selection requires a stable SemVer tag: $release_tag" >&2
        return 1
    fi

    IFS='.' read -r release_major release_minor release_patch <<<"$release_tag"

    while IFS= read -r published_tag; do
        if [[ ! $published_tag =~ $stable_semver_pattern ]] || ! semver_is_greater "$published_tag" "$release_tag"; then
            continue
        fi

        publish_latest=false
        IFS='.' read -r published_major published_minor published_patch <<<"$published_tag"

        if [[ $published_major == "$release_major" ]]; then
            publish_major=false

            if [[ $published_minor == "$release_minor" ]]; then
                publish_minor=false
            fi
        fi
    done

    if [[ $publish_major == true ]]; then
        echo "$release_major"
    fi

    if [[ $publish_minor == true ]]; then
        echo "$release_major.$release_minor"
    fi

    if [[ $publish_latest == true ]]; then
        echo latest
    fi
}

run_self_test() {
    local failures=0

    expect_aliases() {
        local release_tag=$1
        local published_tags=$2
        local expected=$3
        local actual

        actual=$(select_release_aliases "$release_tag" <<<"$published_tags")

        if [[ $actual != "$expected" ]]; then
            echo "Unexpected aliases for $release_tag" >&2
            echo "Expected: ${expected//$'\n'/, }" >&2
            echo "Actual: ${actual//$'\n'/, }" >&2
            failures=$((failures + 1))
        fi
    }

    expect_aliases 1.0.0 '' $'1\n1.0\nlatest'
    expect_aliases 2.0.0 $'1.9.9\n2.0.0' $'2\n2.0\nlatest'
    expect_aliases 1.9.1 $'1.9.1\n2.0.0' $'1\n1.9'
    expect_aliases 1.2.4 $'1.2.4\n1.3.0\n2.0.0' '1.2'
    expect_aliases 1.2.3 $'1.2.4\n1.3.0\n2.0.0' ''
    expect_aliases 3.0.0 $'2.99.99\n4.0.0-rc.1\nv3.0.1' $'3\n3.0\nlatest'
    expect_aliases 99999999999999999999.0.0 '99999999999999999999.0.1' ''

    if select_release_aliases 1.0.0-rc.1 </dev/null >/dev/null 2>&1; then
        echo 'Expected prerelease alias selection to fail' >&2
        failures=$((failures + 1))
    fi

    if [[ $failures -ne 0 ]]; then
        return 1
    fi

    echo 'Release alias selection tests passed'
}

if [[ ${1:-} == --self-test ]]; then
    run_self_test
    exit $?
fi

if [[ $# -ne 1 ]]; then
    echo 'Usage: select-release-aliases.sh <stable-release-tag>' >&2
    exit 1
fi

select_release_aliases "$1"
