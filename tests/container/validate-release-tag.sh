#!/usr/bin/env bash

set -u

validate_release_tag() {
    local tag=$1
    local github_prerelease=$2
    local semver_pattern='^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)(-([0-9A-Za-z-]+(\.[0-9A-Za-z-]+)*))?$'
    local prerelease_identifier
    local prerelease_value
    local expected_prerelease=false

    if [[ ! $tag =~ $semver_pattern ]]; then
        echo "Release tag is not strict SemVer without build metadata: $tag" >&2
        return 1
    fi

    if [[ $tag == *-* ]]; then
        expected_prerelease=true
        prerelease_value=${tag#*-}
        IFS='.' read -r -a prerelease_identifiers <<<"$prerelease_value"

        for prerelease_identifier in "${prerelease_identifiers[@]}"; do
            if [[ $prerelease_identifier =~ ^[0-9]+$ && $prerelease_identifier == 0* && $prerelease_identifier != 0 ]]; then
                echo "Numeric prerelease identifiers must not contain leading zeroes: $tag" >&2
                return 1
            fi
        done
    fi

    if [[ $github_prerelease != true && $github_prerelease != false ]]; then
        echo "GitHub prerelease value must be true or false: $github_prerelease" >&2
        return 1
    fi

    if [[ $github_prerelease != "$expected_prerelease" ]]; then
        echo "GitHub prerelease state does not match release tag: $tag" >&2
        return 1
    fi
}

run_self_test() {
    local failures=0

    expect_valid() {
        if ! validate_release_tag "$1" "$2" >/dev/null 2>&1; then
            echo "Expected a valid release: $1 (prerelease: $2)" >&2
            failures=$((failures + 1))
        fi
    }

    expect_invalid() {
        if validate_release_tag "$1" "$2" >/dev/null 2>&1; then
            echo "Expected an invalid release: $1 (prerelease: $2)" >&2
            failures=$((failures + 1))
        fi
    }

    expect_valid 0.80.0 false
    expect_valid 1.0.0-rc.1 true
    expect_valid 2.3.4-alpha-1 true
    expect_invalid v1.0.0 false
    expect_invalid 01.0.0 false
    expect_invalid 1.01.0 false
    expect_invalid 1.0.01 false
    expect_invalid 1.0.0-rc.01 true
    expect_invalid 1.0.0+build.1 false
    expect_invalid 1.0.0 true
    expect_invalid 1.0.0-rc.1 false

    if [[ $failures -ne 0 ]]; then
        return 1
    fi

    echo 'Release tag validation tests passed'
}

if [[ ${1:-} == --self-test ]]; then
    run_self_test
    exit $?
fi

if [[ $# -ne 2 ]]; then
    echo 'Usage: validate-release-tag.sh <tag> <github-prerelease-boolean>' >&2
    exit 1
fi

validate_release_tag "$1" "$2"
