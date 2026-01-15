# SimpleCart Permissions System

## Overview

The SimpleCart module now includes a granular permissions system that allows administrators to control which user groups can view, create, edit, and delete products and orders.

## Available Permissions

### Product Permissions

| Permission | Name | Description |
|------------|------|-------------|
| View | `simplecart_product_view` | Can view products in the admin area |
| Create | `simplecart_product_create` | Can create new products |
| Edit | `simplecart_product_edit` | Can edit existing products |
| Delete | `simplecart_product_delete` | Can delete products |

### Order Permissions

| Permission | Name | Description |
|------------|------|-------------|
| View | `simplecart_order_view` | Can view orders and the dashboard in the admin area |
| Edit | `simplecart_order_edit` | Can change order status (e.g., from pending to paid) |
| Delete | `simplecart_order_delete` | Can delete orders and all associated order items |

## How to Configure Permissions

1. **Access Permission Management**:
   - Log in as a system administrator
   - Navigate to System Admin → Groups
   - Select the user group you want to configure
   - Go to the "Permissions" or "Module Permissions" section

2. **Set Module Permissions**:
   - Find the "SimpleCart" module in the list
   - Check the permissions you want to grant to this group
   - Click "Submit" to save

3. **Common Permission Configurations**:

   **Shop Manager** (Full product and order access):
   - ✓ View Products
   - ✓ Create Products
   - ✓ Edit Products
   - ✓ Delete Products
   - ✓ View Orders
   - ✓ Edit Orders
   - ✓ Delete Orders

   **Accountant** (Order viewing and status updates only):
   - ✗ View Products
   - ✗ Create Products
   - ✗ Edit Products
   - ✗ Delete Products
   - ✓ View Orders
   - ✓ Edit Orders (for marking orders as paid)
   - ✗ Delete Orders

   **Order Viewer** (Read-only access to orders):
   - ✗ View Products
   - ✗ Create Products
   - ✗ Edit Products
   - ✗ Delete Products
   - ✓ View Orders
   - ✗ Edit Orders
   - ✗ Delete Orders

## Technical Implementation

### Permission Checking

The module uses two main helper functions:

```php
// Check if user has permission (returns true/false)
if (simplecart_hasPermission('simplecart_product_edit')) {
    // User can edit products
}

// Check permission and redirect if denied
simplecart_checkPermission('simplecart_product_view', 'index.php', _NOPERM);
```

### Where Permissions are Checked

1. **Admin Controllers** (`admin/product.php`, `admin/order.php`, `admin/index.php`):
   - Check permissions before displaying any admin interface
   - Redirect users without permission to the appropriate page

2. **Object Handlers** (`class/product.php`, `class/order.php`):
   - Defense-in-depth: validate permissions before database operations
   - Prevents unauthorized operations even if controllers are bypassed

3. **UI Elements**:
   - Create/edit/delete buttons are hidden if user lacks permission
   - Action columns are conditionally displayed based on permissions

### Special Cases

#### Customer Order Creation
Customer order creation via AJAX remains **public** and does not require any permissions. This allows anonymous users to place orders through the shopping cart interface.

#### System Administrators
System administrators automatically have **all permissions** regardless of group settings. This ensures administrators always have full access to manage the module.

## Backward Compatibility

- Existing installations will continue to work without changes
- System administrators retain full access automatically
- Permissions can be configured gradually without breaking functionality
- Default behavior grants no permissions to non-admin groups (secure by default)

## Security Considerations

1. **Defense in Depth**: Permissions are checked at both the controller and handler level
2. **CSRF Protection**: All admin actions use CSRF tokens
3. **Public Operations**: Customer order creation is intentionally unrestricted
4. **Admin Privilege**: System administrators bypass permission checks for administrative tasks

## Troubleshooting

### User Can't Access Admin Area
- Verify the user's group has the appropriate "View" permission
- Check that the user is assigned to a group with SimpleCart module permissions
- System administrators should have automatic access

### Permission Changes Not Taking Effect
- Log out and log back in to refresh permissions
- Clear the module cache if available
- Verify permissions are saved in the group configuration

### Orders Not Being Created
- Customer order creation does not require permissions
- Check for JavaScript errors in the browser console
- Verify AJAX endpoint is accessible

## Developer Notes

### Adding New Permissions

To add new permissions to the module:

1. Add the permission definition in `icms_version.php`:
```php
$modversion['permissions'][$i]['name'] = 'simplecart_new_permission';
$modversion['permissions'][$i]['title'] = '_MI_SIMPLECART_PERM_NEW_TITLE';
$modversion['permissions'][$i]['description'] = '_MI_SIMPLECART_PERM_NEW_DESC';
$i++;
```

2. Add language constants in `language/*/modinfo.php`:
```php
define('_MI_SIMPLECART_PERM_NEW_TITLE', 'Permission Title');
define('_MI_SIMPLECART_PERM_NEW_DESC', 'Permission description');
```

3. Add permission checks in the appropriate controllers and handlers:
```php
simplecart_checkPermission('simplecart_new_permission', 'redirect_url.php', _NOPERM);
```

### Permission Helper Functions

Located in `include/common.php`:

- `simplecart_hasPermission($permission)`: Returns boolean indicating if current user has permission
- `simplecart_checkPermission($permission, $redirect_url, $message)`: Redirects with error if permission denied

Both functions use ImpressCMS's native `icms_member_groupperm_Handler` for permission checking.

## Support

For questions or issues with the permissions system:
- Check this documentation
- Review the code in `include/common.php` for implementation details
- Consult ImpressCMS documentation for group permission management
