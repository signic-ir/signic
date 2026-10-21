#!/bin/bash
# Run a single Cypress spec inside the containerised cypress runner.
# Usage: run_spec.sh <feature-path>
set -e
cd /home/hamid/signic/cypress-bdd
OUT=/tmp/cy_$(basename "$1" .feature).log
docker exec cypress-bdd-runner npx cypress run --spec "$1" --browser electron --config video=false > "$OUT" 2>&1
echo "EXIT=$?" >> "$OUT"
sed 's/\x1b\[[0-9;]*m//g' "$OUT" | grep -vE "^\s*$" | tail -80