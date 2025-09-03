#!/bin/bash

# Backend Role API Test Script
# This script tests the role update functionality using curl

BASE_URL="http://localhost:8000"
TEST_EMAIL="test@example.com"
TEST_PASSWORD="password"

echo "=== Backend Role API Test ==="
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

# Step 1: Login and get token
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
    exit 1
fi

echo ""

# Step 2: Get user profile
echo "2. Testing get profile..."
PROFILE_RESPONSE=$(curl -s -X GET "$BASE_URL/api/auth/user" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json")

USER_UUID=$(echo $PROFILE_RESPONSE | grep -o '"uuid":"[^"]*"' | cut -d'"' -f4)
CURRENT_ROLES=$(echo $PROFILE_RESPONSE | grep -o '"roles":\[[^]]*\]' | cut -d'[' -f2 | cut -d']' -f1)

if [ -n "$USER_UUID" ]; then
    print_status 0 "Profile retrieved successfully"
    echo "   User UUID: $USER_UUID"
    echo "   Current roles: $CURRENT_ROLES"
else
    print_status 1 "Get profile failed"
    echo "   Response: $PROFILE_RESPONSE"
    exit 1
fi

echo ""

# Step 3: Test role update
echo "3. Testing role update..."
NEW_ROLE="user"
ROLE_UPDATE_RESPONSE=$(curl -s -X POST "$BASE_URL/api/users/$USER_UUID/role" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "{
        \"role\": \"$NEW_ROLE\"
    }")

if echo "$ROLE_UPDATE_RESPONSE" | grep -q "updated"; then
    print_status 0 "Role updated successfully"
    echo "   New role: $NEW_ROLE"
else
    print_status 1 "Role update failed"
    echo "   Response: $ROLE_UPDATE_RESPONSE"
fi

echo ""

# Step 4: Test role validation (invalid role)
echo "4. Testing role validation (invalid role)..."
INVALID_ROLE_RESPONSE=$(curl -s -X POST "$BASE_URL/api/users/$USER_UUID/role" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "{
        \"role\": \"invalid_role_that_does_not_exist\"
    }")

if echo "$INVALID_ROLE_RESPONSE" | grep -q "422"; then
    print_status 0 "Invalid role validation working"
else
    print_status 1 "Invalid role validation failed"
    echo "   Response: $INVALID_ROLE_RESPONSE"
fi

echo ""

# Step 5: Test role validation (missing role)
echo "5. Testing role validation (missing role)..."
MISSING_ROLE_RESPONSE=$(curl -s -X POST "$BASE_URL/api/users/$USER_UUID/role" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "{}")

if echo "$MISSING_ROLE_RESPONSE" | grep -q "422"; then
    print_status 0 "Missing role validation working"
else
    print_status 1 "Missing role validation failed"
    echo "   Response: $MISSING_ROLE_RESPONSE"
fi

echo ""

# Step 6: Test permission check (update other user's role)
echo "6. Testing permission check (update other user's role)..."
OTHER_USER_UUID="00000000-0000-0000-0000-000000000000" # Dummy UUID
PERMISSION_RESPONSE=$(curl -s -X POST "$BASE_URL/api/users/$OTHER_USER_UUID/role" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "{
        \"role\": \"user\"
    }")

if echo "$PERMISSION_RESPONSE" | grep -q "403"; then
    print_status 0 "Permission check working (can't update other user's role)"
else
    print_status 1 "Permission check failed"
    echo "   Response: $PERMISSION_RESPONSE"
fi

echo ""
echo "=== Test completed ==="
