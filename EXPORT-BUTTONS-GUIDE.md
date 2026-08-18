# Export Buttons Location Guide

## Where to Find Export Buttons

### 1. Supplier Dashboard (supplier-dashboard.php)
**Location:** Bottom of the page in "Export Reports" section

**Buttons Available:**
- Products Report
  - 📄 CSV - Download products spreadsheet
  - 📋 PDF - Print/save products report
- Orders Report
  - 📄 CSV - Download orders spreadsheet
  - 📋 PDF - Print/save orders report

**Visual Layout:**
```
┌─────────────────────────────────────────────────┐
│  📥 Export Reports                              │
│  Download your data in CSV or PDF format        │
│                                                  │
│  ┌──────────────────┐  ┌──────────────────┐   │
│  │ 📦 Products      │  │ 🛒 Orders        │   │
│  │ Report           │  │ Report           │   │
│  │                  │  │                  │   │
│  │ [CSV]  [PDF]     │  │ [CSV]  [PDF]     │   │
│  └──────────────────┘  └──────────────────┘   │
└─────────────────────────────────────────────────┘
```

### 2. My Products Page (product-list.php)
**Location:** Top right corner, next to "Add New Product" button

**Buttons Available:**
- 📄 Export CSV - Download all products
- 📋 Export PDF - Print/save products report
- ➕ Add New Product

**Visual Layout:**
```
┌─────────────────────────────────────────────────┐
│  🏪 My Products                                 │
│                    [Export CSV] [Export PDF] [➕ Add New Product] │
└─────────────────────────────────────────────────┘
```

### 3. Orders Page (supplier-orders.php)
**Location:** Top right corner of the page

**Buttons Available:**
- 📄 Export CSV - Download all orders
- 📋 Export PDF - Print/save orders report

**Visual Layout:**
```
┌─────────────────────────────────────────────────┐
│  🛒 Orders for My Products                      │
│                    [Export CSV] [Export PDF]    │
└─────────────────────────────────────────────────┘
```

## Button Colors

- **CSV Button** - Green (Success color)
  - Indicates data export for spreadsheets
  
- **PDF Button** - Blue (Primary color)
  - Indicates printable report generation

## What Happens When You Click

### CSV Export
1. Click the CSV button
2. File downloads automatically
3. Filename includes current date (e.g., `my_products_2024-11-24.csv`)
4. Open with Excel, Google Sheets, or any spreadsheet app

### PDF Export
1. Click the PDF button
2. New tab opens with formatted report
3. Browser's print dialog appears automatically
4. Choose "Save as PDF" or print directly
5. Professional report with statistics and branding

## Quick Tips

✅ **CSV is best for:**
- Data analysis in Excel/Sheets
- Importing into other systems
- Creating charts and graphs
- Filtering and sorting data

✅ **PDF is best for:**
- Printing physical copies
- Sharing with stakeholders
- Professional presentations
- Record keeping and archiving

## File Naming Convention

- Products CSV: `my_products_YYYY-MM-DD.csv`
- Orders CSV: `my_orders_YYYY-MM-DD.csv`
- PDF files: Use browser's default naming or customize when saving
