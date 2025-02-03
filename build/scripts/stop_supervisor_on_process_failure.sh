#!/bin/bash

# Infinite loop to listen for events
while true; do
    # Wait for an event from Supervisor
    read -r event

    # If the event is PROCESS_STATE_FATAL, exit with a non-zero code to stop Supervisor
    if [[ "$event" == *"PROCESS_STATE_FATAL"* ]]; then
        echo "A process has failed. Stopping Supervisor."
        exit 1  # Exit with status 1 to stop Supervisor
    fi
done