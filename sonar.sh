#!/bin/bash
set -e

SONAR_TOKEN=$(grep '^SONAR_TOKEN=' .env | cut -d '=' -f2)

if [ -z "$SONAR_TOKEN" ]; then
    echo "Error: SONAR_TOKEN not found in .env"
    exit 1
fi

PROJECT=$(basename "$(pwd)")
NETWORK="${PROJECT}_sail"

docker run --rm \
    --network "$NETWORK" \
    -v "$(pwd)":/usr/src \
    -w /usr/src \
    sonarsource/sonar-scanner-cli \
    -Dsonar.token="$SONAR_TOKEN"
