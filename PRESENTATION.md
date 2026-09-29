# Isa & Dagi Mobile Shop — Project Presentation

---

## SLIDE 1 — Project Title

```
🛍️  ISA & DAGI MOBILE SHOP
     E-Commerce Web Application

📍  Merkato, Samson Building — Addis Ababa, Ethiopia
🛠️  Built with: PHP · MySQL · Bootstrap 5 · JavaScript
```

---

## SLIDE 2 — What Is This Project?

> A **full-stack online mobile shop** where customers can browse,
> buy phones & accessories — and admins manage everything from a dashboard.

**Two sides:**
| 👤 Customer Side | 🔧 Admin Side |
|---|---|
| Browse & buy products | Manage products & stock |
| Cart & Wishlist | Manage orders |
| Checkout & order tracking | Manage users |
| Blog & Support | Settings & reports |

---

## SLIDE 3 — System Architecture

```
┌─────────────────────────────────────────┐
│              WEB BROWSER                │
│         (Customer / Admin)              │
└──────────────┬──────────────────────────┘
               │  HTTP Request
               ▼
┌─────────────────────────────────────────┐
│           PHP APPLICATION               │
│  ┌─────────────┐   ┌─────────────────┐  │
│  │  Customer   │   │   Admin Panel   │  │
│  │   Pages     │   │   /admin/       │  │
│  └──────┬──────┘   └────────┬────────┘  │
│         └─────────┬─────────┘           │
│                   ▼                     │
│         ┌──────────────────┐            │
│         │  Database Layer  │            │
│         │  (DBController)  │            │
│         └────────┬─────────┘            │
└──────────────────┼──────────────────────┘
                   ▼
        ┌──────────────────────┐
        │   MySQL Database     │
        │   (shopee)           │
        └──────────────────────┘
```

---

## SLIDE 4 — Database Tables

```
📦 product        — phones catalog
🎧 accessories    — phone accessories
🏷️  brand          — phone brands
👤 user           — customers & admins
🛒 cart           — shopping cart items
❤️  wishlist       — saved for later
📋 orders         — placed orders
📄 order_items    — items per order
⚙️  settings       — shop configuration
📰 newsletter     — email subscribers
💬 contact_messages — support messages
📝 blog           — blog posts
```

---

## SLIDE 5 — Customer Workflow

```
1. VISIT SHOP
   └─► Browse homepage (banners, top sale, new arrivals)

2. FIND PRODUCT
   └─► Search by name / filter by brand / on sale

3. VIEW PRODUCT
   └─► See specs, price, stock, related products

4. ADD TO CART
   └─► Login required → Register or Sign in

5. CHECKOUT
   └─► Fill delivery info → Choose payment → Place Order

6. TRACK ORDER
   └─► My Orders → See status (Pending → Shipped → Delivered)
```

---

## SLIDE 6 — User Authentication Flow

```
Click "Login / Register"
         │
         ▼
   Enter Email
         │
         ▼
   ┌─────┴──────┐
   │            │
Email        New Email
Exists?      (New User)
   │            │
   ▼            ▼
LOGIN        REGISTER
FORM         FORM
   │            │
   └─────┬──────┘
         ▼
   Session Created
   → Redirect / Reload
```

---

## SLIDE 7 — Shopping Cart & Wishlist

```
CART                          WISHLIST
─────────────────────         ─────────────────────
✅ Add products               ❤️  Save for later
✅ Increase / Decrease qty    ✅ Move to cart
✅ Remove items               ✅ Remove items
✅ Live price update          ✅ Always visible
✅ Order summary              ✅ Count shown in navbar
✅ Proceed to Checkout
```

---

## SLIDE 8 — Checkout & Orders

```
CHECKOUT PAGE
─────────────────────────────────────
• Full Name, Email, Phone
• Delivery Address & City
• Payment Method:
    💵 Cash on Delivery
    🏦 Bank Transfer
    📱 Telebirr
    💳 CBE Birr
• Place Order → Confirmation shown

ORDER TRACKING
─────────────────────────────────────
Pending → Processing → Shipped → Delivered
Customer can cancel if still Pending/Processing
```

---

## SLIDE 9 — Admin Panel

```
📊 DASHBOARD
   └─► Stats: Products, Orders, Revenue, Users
   └─► Low stock alerts
   └─► Recent orders table

📦 PRODUCTS
   └─► Add / Edit / Delete phones
   └─► Upload image, set specs, price, stock

🎧 ACCESSORIES
   └─► Manage phone accessories

📋 ORDERS
   └─► View all orders, update status

👥 USERS
   └─► View all registered customers

⚙️  SETTINGS
   └─► Currency rate, shop info, contact messages
```

---

## SLIDE 10 — Key Features Summary

| Feature | Details |
|---|---|
| 🔐 Secure Auth | Bcrypt password hashing, session management |
| 🔍 Smart Search | Search by name, filter by brand/price/type |
| 🛒 Live Cart | Qty update without page reload (AJAX) |
| 💱 Currency | Switch between USD and ETB Birr |
| 📱 Responsive | Works on mobile, tablet, desktop |
| 📧 Newsletter | Email subscription system |
| 💬 Support | Contact form + FAQ page |
| 📝 Blog | News and tips section |
| 🔔 Low Stock | Admin gets alerts when stock ≤ 5 |
| 📦 Order Tracking | Step-by-step order progress tracker |

---

## SLIDE 11 — Technology Stack

```
FRONTEND                    BACKEND
────────────────────        ────────────────────
HTML5 / CSS3                PHP 8+
Bootstrap 5                 MySQL Database
JavaScript / jQuery         Session Management
Owl Carousel                Password Hashing
Font Awesome Icons          AJAX Handlers
Google Fonts
```

---

## SLIDE 12 — Thank You

```
        🏪 ISA & DAGI MOBILE SHOP

   "Your trusted mobile destination
    in Addis Ababa"

   📍 Merkato, Samson Building
   📞 +251 911 000 000
   ✉️  info@isadagi.com
   💬 Telegram: @isadagishop
```

---
