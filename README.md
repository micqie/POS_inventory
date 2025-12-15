# POS Inventory (PHP + MySQL)

Lightweight modular POS/inventory sample built on your existing `connect_db.php` and `pos_db.sql`.

## Setup
1) Import schema  
`mysql -u root pos_db < pos_db.sql`

2) Create admin account  
Visit `http://localhost/POS_inventory/seed_admin.php` once (creates admin/admin123).

3) Start app  
Open `http://localhost/POS_inventory/index.php` and log in.

## Modules
- Dashboard overview
- Categories CRUD
- Products CRUD (price, stock)
- Suppliers CRUD
- Customers CRUD
- Inventory transactions (in/out adjusts stock)
- Sales with cart, stock decrement, sale details, auto inventory out
- Users CRUD (admin only)

## Notes
- Credentials stored with `password_hash`.
- UI intentionally minimal; extend styling or add validation as needed.