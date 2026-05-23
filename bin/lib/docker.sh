#!/bin/bash

container_running() {
    local name=$1
    docker inspect -f '{{.State.Running}}' "$name" 2>/dev/null | grep -q "true"
}

require_container_running() {
    local name=$1
    if ! container_running "$name"; then
        echo "❌ Container not running: $name"
        exit 1
    fi
}