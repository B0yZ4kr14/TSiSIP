#!/usr/bin/env bash
# TSiSIP Bash Completion

_tsisip() {
    local cur="${COMP_WORDS[COMP_CWORD]}"
    local commands="build up down logs test backup restore monitor install update clean lint format status health-check benchmark load-test migrate seed reset validate sync report size-check security-scan dependency-check git-stats cleanup-old"
    COMPREPLY=($(compgen -W "$commands" -- "$cur"))
}

complete -F _tsisip tsisip
