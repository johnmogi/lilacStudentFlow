#!/bin/bash
# WordPress Cleanup Script for Local by Flywheel
# Run this in your Local site's shell

# Exit on error
set -e

echo "🚀 Starting WordPress cleanup process..."

# 1. Deactivate and delete old plugins
echo "🔌 Removing old plugins..."
wp plugin deactivate ultimate-member paid-memberships-pro || echo "Plugins already deactivated or not found"
wp plugin delete ultimate-member paid-memberships-pro || echo "Plugins already deleted or not found"

# 2. Clean up plugin tables
echo "🧹 Cleaning up plugin tables..."
echo "📋 The following tables will be dropped if they exist:"
wp db query "SHOW TABLES LIKE 'edc_um_%';" || true
wp db query "SHOW TABLES LIKE 'edc_pmpro_%';" || true

# Drop tables (commented out for safety - uncomment after verification)
# echo "🗑️  Dropping tables..."
# wp db query "DROP TABLE IF EXISTS $(wp db tables 'edc_um_*' --all-tables-with-prefix --format=plain);"
# wp db query "DROP TABLE IF EXISTS $(wp db tables 'edc_pmpro_*' --all-tables-with-prefix --format=plain);"

# 3. Clean up options
echo "🧼 Removing plugin options..."
for prefix in um_ pmpro_; do
    wp option list --search="$prefix"* --field=option_name | xargs -r wp option delete
done

# 4. Clean user meta
echo "👤 Cleaning user meta..."
for meta_key in um_user_profile_visibility pmpro_level; do
    wp user meta delete --all "$meta_key" || true
done

# 5. Create roles if they don't exist
echo "👥 Setting up roles..."
for role in "student_private=תלמיד עצמאי" "student_school=תלמיד חינוך תעבורתי" "school_teacher=מורה / רכז"; do
    role_name="${role%=*}"
    role_display="${role#*=}"
    if ! wp role list --fields=role --format=csv | grep -q "^$role_name$"; then
        wp role create "$role_name" "$role_display" --quiet
        echo "✅ Created role: $role_name ($role_display)"
    else
        echo "ℹ️  Role exists: $role_name"
    fi
done

# 6. Count users by role
echo ""
echo "📊 User counts by role:"
for role in student_private student_school school_teacher; do
    count=$(wp user list --role="$role" --format=count 2>/dev/null || echo "0")
    echo "   $role: $count users"
done

echo ""
echo "✨ Cleanup complete!"
echo "💡 Run 'wp user list --role=ROLE' to see users in each role"