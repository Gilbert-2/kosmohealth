#!/bin/bash

# Test script for the /api/auth/update-role endpoint
# This script tests the exact endpoint that the frontend is calling

BASE_URL="http://127.0.0.1:8000"
TEST_EMAIL="test@example.com"
TEST_PASSWORD="password"

echo "=== Testing /api/auth/update-role Endpoint ==="
echo "Base URL: $BASE_URL"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓ $2${NC}"
    else
        echo -e "${RED}✗ $2${NC}"
    fi
}

# Step 1: Test login
echo "1. Testing login..."
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/api/auth/login" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "{
        \"email\": \"$TEST_EMAIL\",
        \"password\": \"$TEST_PASSWORD\"
    }")

TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

if [ -n "$TOKEN" ]; then
    print_status 0 "Login successful"
    echo "   Token: ${TOKEN:0:20}..."
else
    print_status 1 "Login failed"
    echo "   Response: $LOGIN_RESPONSE"
    echo "   Note: You may need to update TEST_EMAIL and TEST_PASSWORD with valid credentials"
    exit 1
fi

echo ""

# Step 2: Test the new update-role endpoint
echo "2. Testing /api/auth/update-role endpoint..."
NEW_ROLE="user"
ROLE_UPDATE_RESPONSE=$(curl -s -X POST "$BASE_URL/api/auth/update-role" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "{
        \"role\": \"$NEW_ROLE\"
    }")

if echo "$ROLE_UPDATE_RESPONSE" | grep -q "updated"; then
    print_status 0 "Role updated successfully via /api/auth/update-role"
    echo "   New role: $NEW_ROLE"
    echo "   Response: $ROLE_UPDATE_RESPONSE"
elif echo "$ROLE_UPDATE_RESPONSE" | grep -q "403"; then
    print_status 1 "Role update failed with 403 Forbidden"
    echo "   Response: $ROLE_UPDATE_RESPONSE"
    echo "   This means the endpoint exists but there's a permission issue"
elif echo "$ROLE_UPDATE_RESPONSE" | grep -q "404"; then
    print_status 1 "Role update failed with 404 Not Found"
    echo "   Response: $ROLE_UPDATE_RESPONSE"
    echo "   This means the endpoint doesn't exist"
else
    print_status 1 "Role update failed with unexpected response"
    echo "   Response: $ROLE_UPDATE_RESPONSE"
fi

echo ""

# Step 3: Test validation (invalid role)
echo "3. Testing validation (invalid role)..."
INVALID_ROLE_RESPONSE=$(curl -s -X POST "$BASE_URL/api/auth/update-role" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "{
        \"role\": \"invalid_role_that_does_not_exist\"
    }")

if echo "$INVALID_ROLE_RESPONSE" | grep -q "422"; then
    print_status 0 "Invalid role validation working"
elif echo "$INVALID_ROLE_RESPONSE" | grep -q "Invalid role"; then
    print_status 0 "Invalid role validation working"
else
    print_status 1 "Invalid role validation may not be working properly"
    echo "   Response: $INVALID_ROLE_RESPONSE"
fi

echo ""

# Step 4: Test validation (missing role)
echo "4. Testing validation (missing role)..."
MISSING_ROLE_RESPONSE=$(curl -s -X POST "$BASE_URL/api/auth/update-role" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "{}")

if echo "$MISSING_ROLE_RESPONSE" | grep -q "422"; then
    print_status 0 "Missing role validation working"
elif echo "$MISSING_ROLE_RESPONSE" | grep -q "required"; then
    print_status 0 "Missing role validation working"
else
    print_status 1 "Missing role validation may not be working properly"
    echo "   Response: $MISSING_ROLE_RESPONSE"
fi

echo ""

# Step 5: Test without authentication
echo "5. Testing without authentication..."
UNAUTH_RESPONSE=$(curl -s -X POST "$BASE_URL/api/auth/update-role" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "{
        \"role\": \"user\"
    }")

if echo "$UNAUTH_RESPONSE" | grep -q "401"; then
    print_status 0 "Unauthenticated access properly blocked"
elif echo "$UNAUTH_RESPONSE" | grep -q "Unauthenticated"; then
    print_status 0 "Unauthenticated access properly blocked"
else
    print_status 1 "Unauthenticated access may not be properly secured"
    echo "   Response: $UNAUTH_RESPONSE"
fi

echo ""
echo "=== Test completed ==="
echo ""
echo "To access the Swagger documentation, visit:"
echo "http://127.0.0.1:8000/api-docs"
echo ""
echo "The /api/auth/update-role endpoint should now be working!"
