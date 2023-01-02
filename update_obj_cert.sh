#!/bin/bash -e

# Reference: https://www.linode.com/docs/api/object-storage

CLUSTER_ID=us-east-1
BUCKET=obj.vassalengine.org

CERT_PATH=/etc/letsencrypt/live/obj.vassalengine.org/fullchain.pem
PRIV_PATH=/etc/letsencrypt/live/obj.vassalengine.org/privkey.pem

API_URL="https://api.linode.com/v4/object-storage/buckets/$CLUSTER_ID/$BUCKET/ssl"

TOKEN=$(cat obj_cert_token)

# Delete the old keypair
curl -H "Authorization: Bearer $TOKEN" -X DELETE "$API_URL"

# Post the new keypair
CERT=$(jq -Rsa . <$CERT_PATH)
PRIV=$(jq -Rsa . <$PRIV_PATH)

curl -H "Content-Type: application/json" -H "Authorization: Bearer $TOKEN" -X POST -d "{ \"certificate\": $CERT, \"private_key\": $PRIV }" $API_URL
