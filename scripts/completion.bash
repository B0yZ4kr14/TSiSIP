#!/usr/bin/env bash
# TSiSIP Bash Completion

_tsisip_completions() {
    local cur="${COMP_WORDS[COMP_CWORD]}"
    local cmds="build up down logs test backup restore monitor install update clean"
    COMPREPLY=($(compgen -W "$cmds" -- "$cur"))
}

complete -F _tsisip_completions make
