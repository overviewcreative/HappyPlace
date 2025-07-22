# Airtable Integration for Happy Place Plugin

This integration provides comprehensive two-way synchronization between WordPress and Airtable for real estate listings.

## 🚀 Features

- **Two-way sync** between WordPress and Airtable
- **Access token support** (modern authentication)
- **Comprehensive field validation** and sanitization
- **Real-time table structure validation**
- **Automated table setup template**
- **Detailed error reporting** and sync statistics
- **AJAX admin interface** for easy management
- **Cron scheduling** support

## 📋 Quick Setup

### 1. Airtable Setup

1. **Create Account**: Go to [Airtable.com](https://airtable.com) and create an account
2. **Create Base**: Create a new base or use existing one
3. **Download Template**: Use the provided JSON template or setup instructions
4. **Get Credentials**:
   - **Base ID**: Copy from URL (starts with `app...`)
   - **Access Token**: Create at [airtable.com/create/tokens](https://airtable.com/create/tokens)

### 2. WordPress Configuration

1. Navigate to **Happy Place → Integrations**
2. Enter your Airtable Base ID and Access Token
3. Test the connection
4. Configure sync settings

## 🗂️ Table Structure

### Required Fields

| Field Name | Type | Description |
|------------|------|-------------|
| **Property Name** | Single Line Text | Property title (Required) |
| Record ID | Single Line Text | WordPress sync identifier |
| Street Address | Single Line Text | Full address |
| City | Single Line Text | City name |
| State | Single Line Text | State (2 chars) |
| ZIP Code | Single Line Text | ZIP code |
| List Price | Currency | Property price |
| Bedrooms | Number | Number of bedrooms |
| Bathrooms | Number | Number of bathrooms |
| Square Footage | Number | Total square footage |
| Property Type | Single Select | Type of property |
| Listing Status | Single Select | Current status |

### Field Options

**Property Type Options:**
- Single Family
- Condo  
- Townhouse
- Multi-Family
- Land
- Commercial

**Listing Status Options:**
- active
- pending
- sold
- withdrawn
- expired

## 🔧 Usage Examples

### Test Connection

```php
$sync = new \HappyPlace\Integrations\Airtable_Two_Way_Sync('your_base_id', 'Real Estate Listings');
$result = $sync->test_api_connection();

if ($result['success']) {
    echo "Connected successfully!";
} else {
    echo "Connection failed: " . $result['message'];
}
```

### Manual Sync

```php
// Trigger complete sync
$results = hph_trigger_airtable_sync('your_base_id', 'Real Estate Listings');

if ($results['success']) {
    echo "Sync completed!";
    echo "Created: " . $results['airtable_to_wp']['stats']['created'];
    echo "Updated: " . $results['airtable_to_wp']['stats']['updated'];
} else {
    echo "Sync failed: " . $results['error'];
}
```

### Validate Table Structure

```php
$sync = new \HappyPlace\Integrations\Airtable_Two_Way_Sync('your_base_id', 'Real Estate Listings');
$validation = $sync->validate_table_structure();

if ($validation['valid']) {
    echo "Table structure is correct!";
} else {
    echo "Missing fields: " . implode(', ', $validation['missing_fields']);
}
```

## 🎛️ AJAX Endpoints

The integration provides several AJAX endpoints for admin interfaces:

- `hph_test_airtable_connection` - Test API connection
- `hph_airtable_sync` - Run manual sync
- `hph_get_airtable_template` - Get table template
- `hph_validate_airtable_table` - Validate table structure
- `hph_download_airtable_template` - Download JSON template

### JavaScript Example

```javascript
// Test connection
jQuery.post(ajaxurl, {
    action: 'hph_test_airtable_connection',
    nonce: 'your_nonce',
    base_id: 'your_base_id',
    table_name: 'Real Estate Listings'
}, function(response) {
    if (response.success) {
        alert('Connection successful!');
    } else {
        alert('Connection failed: ' + response.message);
    }
});
```

## 📊 Data Validation

### Field Validation Rules

- **Price**: 0 to $50,000,000
- **Bedrooms**: 0 to 20
- **Bathrooms**: 0 to 20 (allows decimals)
- **Square Footage**: 0 to 50,000
- **Year Built**: 1800 to 2030
- **ZIP Code**: Must match format `12345` or `12345-6789`
- **Coordinates**: Latitude (-90 to 90), Longitude (-180 to 180)

### Sanitization

All data is sanitized using WordPress functions:
- Text fields: `sanitize_text_field()`
- Textarea: `sanitize_textarea_field()`
- Numbers: `intval()` or `floatval()`
- Select options: Validated against allowed values

## 🔄 Sync Process

### Airtable → WordPress

1. Fetch all records from Airtable
2. Validate and sanitize each field
3. Create or update WordPress posts
4. Update ACF custom fields
5. Store sync metadata

### WordPress → Airtable

1. Query all WordPress listings
2. Extract and validate field data
3. Create or update Airtable records
4. Update sync timestamps

## 📈 Error Handling

### Sync Statistics

Each sync operation provides detailed statistics:

```php
[
    'created' => 5,
    'updated' => 12,
    'skipped' => 2,
    'errors' => 1
]
```

### Validation Errors

Detailed error tracking per record:

```php
[
    'rec123abc' => [
        'price' => 'Value exceeds maximum allowed',
        'zip_code' => 'Invalid format'
    ]
]
```

## 🔐 Security

- **Access Token Authentication**: Uses modern Airtable access tokens
- **Permission Checking**: Requires `manage_options` capability
- **Nonce Verification**: All AJAX requests use WordPress nonces
- **Input Sanitization**: All data is sanitized before processing
- **Error Logging**: Sensitive errors logged server-side only

## ⏰ Cron Scheduling

Set up automatic syncing with WordPress cron:

```php
// Schedule twice daily sync
if (!wp_next_scheduled('happy_place_airtable_sync_cron')) {
    wp_schedule_event(time(), 'twicedaily', 'happy_place_airtable_sync_cron');
}

add_action('happy_place_airtable_sync_cron', 'hph_trigger_airtable_sync');
```

## 🐛 Troubleshooting

### Common Issues

**Connection Failed:**
- Verify access token has correct permissions
- Check Base ID format (starts with `app`)
- Ensure table name matches exactly

**Sync Errors:**
- Check required field "Property Name" is not empty
- Verify select field options match allowed values
- Review validation error details in sync results

**Missing Data:**
- Confirm all field names match template exactly
- Check field type compatibility
- Review sync statistics for skipped records

### Debug Mode

Enable detailed logging by adding to `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Sync results and errors will be logged to `/wp-content/debug.log`.

## 📚 Files Structure

```
includes/integrations/
├── class-airtable-two-way-sync.php    # Main sync class
├── class-airtable-setup-helper.php    # Setup utilities
├── airtable-table-template.json       # Table structure template
├── airtable-usage-examples.php        # Usage examples
└── README.md                          # This documentation
```

## 🎯 Best Practices

1. **Test First**: Always test connection before running sync
2. **Backup Data**: Backup both WordPress and Airtable before initial sync
3. **Monitor Logs**: Check error logs after sync operations
4. **Validate Structure**: Use table validation before going live
5. **Schedule Wisely**: Don't over-schedule automatic syncs
6. **Handle Errors**: Review validation errors and fix data issues

## 🔗 Helpful Links

- [Airtable API Documentation](https://airtable.com/developers/web/api/introduction)
- [Create Personal Access Token](https://airtable.com/create/tokens)
- [WordPress Cron Documentation](https://developer.wordpress.org/plugins/cron/)

---

**Happy Place Plugin v1.0**  
Real Estate Website Integration Suite
