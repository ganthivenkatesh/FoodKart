// FoodKart - Static Version for GitHub Pages
// Demo data and functionality

// Demo Restaurant Data
const restaurants = [
    {
        id: 1,
        name: "Spice Garden",
        cuisine: "Indian",
        rating: 4.5,
        deliveryTime: "30-40 min",
        image: "https://via.placeholder.com/300x200?text=Spice+Garden",
        type: "veg",
        menu: [
            { id: 1, name: "Butter Chicken", price: 250, type: "non-veg", image: "https://via.placeholder.com/100x100?text=Butter+Chicken" },
            { id: 2, name: "Paneer Tikka", price: 200, type: "veg", image: "https://via.placeholder.com/100x100?text=Paneer+Tikka" },
            { id: 3, name: "Veg Biryani", price: 180, type: "veg", image: "https://via.placeholder.com/100x100?text=Veg+Biryani" }
        ]
    },
    {
        id: 2,
        name: "Burger Palace",
        cuisine: "American",
        rating: 4.2,
        deliveryTime: "25-35 min",
        image: "https://via.placeholder.com/300x200?text=Burger+Palace",
        type: "non-veg",
        menu: [
            { id: 4, name: "Classic Burger", price: 150, type: "non-veg", image: "https://via.placeholder.com/100x100?text=Classic+Burger" },
            { id: 5, name: "Veggie Burger", price: 120, type: "veg", image: "https://via.placeholder.com/100x100?text=Veggie+Burger" },
            { id: 6, name: "Combo Meal", price: 250, type: "combo", image: "https://via.placeholder.com/100x100?text=Combo+Meal" }
        ]
    },
    {
        id: 3,
        name: "Pizza Express",
        cuisine: "Italian",
        rating: 4.7,
        deliveryTime: "35-45 min",
        image: "https://via.placeholder.com/300x200?text=Pizza+Express",
        type: "veg",
        menu: [
            { id: 7, name: "Margherita Pizza", price: 300, type: "veg", image: "https://via.placeholder.com/100x100?text=Margherita+Pizza" },
            { id: 8, name: "Pepperoni Pizza", price: 350, type: "non-veg", image: "https://via.placeholder.com/100x100?text=Pepperoni+Pizza" },
            { id: 9, name: "Family Combo", price: 800, type: "combo", image: "https://via.placeholder.com/100x100?text=Family+Combo" }
        ]
    }
];

// Cart functionality
let cart = [];

// Initialize the app
document.addEventListener('DOMContentLoaded', function() {
    loadRestaurants();
    loadMenuItems();
    setupEventListeners();
});

// Load restaurants
function loadRestaurants(filter = 'all') {
    const grid = document.getElementById('restaurants-grid');
    grid.innerHTML = '';
    
    const filtered = filter === 'all' ? restaurants : restaurants.filter(r => r.type === filter);
    
    filtered.forEach(restaurant => {
        const card = document.createElement('div');
        card.className = 'restaurant-card';
        card.innerHTML = `
            <img src="${restaurant.image}" alt="${restaurant.name}">
            <div class="restaurant-info">
                <h3>${restaurant.name}</h3>
                <p>${restaurant.cuisine} • ${restaurant.deliveryTime}</p>
                <div class="rating">
                    <i class="fas fa-star"></i> ${restaurant.rating}
                </div>
                <button class="btn btn-primary" onclick="viewMenu(${restaurant.id})">View Menu</button>
            </div>
        `;
        grid.appendChild(card);
    });
}

// Load menu items
function loadMenuItems() {
    const grid = document.getElementById('menu-grid');
    grid.innerHTML = '';
    
    const allItems = restaurants.flatMap(r => r.menu);
    
    allItems.forEach(item => {
        const card = document.createElement('div');
        card.className = 'menu-item';
        card.innerHTML = `
            <img src="${item.image}" alt="${item.name}">
            <div class="menu-item-info">
                <h4>${item.name}</h4>
                <p>₹${item.price}</p>
                <span class="type-badge ${item.type}">${item.type}</span>
                <button class="btn btn-primary" onclick="addToCart(${item.id})">Add to Cart</button>
            </div>
        `;
        grid.appendChild(card);
    });
}

// Setup event listeners
function setupEventListeners() {
    // Filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            loadRestaurants(this.dataset.filter);
        });
    });
    
    // Cart modal
    const cartModal = document.getElementById('cart-modal');
    const cartIcon = document.querySelector('.cart-icon');
    const closeBtn = document.querySelector('.close');
    
    cartIcon.addEventListener('click', () => {
        cartModal.style.display = 'block';
        updateCartDisplay();
    });
    
    closeBtn.addEventListener('click', () => {
        cartModal.style.display = 'none';
    });
    
    window.addEventListener('click', (e) => {
        if (e.target === cartModal) {
            cartModal.style.display = 'none';
        }
    });
}

// Add to cart
function addToCart(itemId) {
    const allItems = restaurants.flatMap(r => r.menu);
    const item = allItems.find(i => i.id === itemId);
    
    const existingItem = cart.find(i => i.id === itemId);
    if (existingItem) {
        existingItem.quantity++;
    } else {
        cart.push({ ...item, quantity: 1 });
    }
    
    updateCartCount();
    showNotification('Item added to cart!');
}

// Update cart count
function updateCartCount() {
    const count = cart.reduce((sum, item) => sum + item.quantity, 0);
    document.getElementById('cart-count').textContent = count;
}

// Update cart display
function updateCartDisplay() {
    const cartItems = document.getElementById('cart-items');
    const cartTotal = document.getElementById('cart-total');
    
    if (cart.length === 0) {
        cartItems.innerHTML = '<p>Your cart is empty</p>';
        cartTotal.textContent = '0';
        return;
    }
    
    let html = '';
    let total = 0;
    
    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;
        html += `
            <div class="cart-item">
                <img src="${item.image}" alt="${item.name}">
                <div class="cart-item-info">
                    <h4>${item.name}</h4>
                    <p>₹${item.price} x ${item.quantity}</p>
                </div>
                <div class="cart-item-actions">
                    <button onclick="updateQuantity(${item.id}, -1)">-</button>
                    <span>${item.quantity}</span>
                    <button onclick="updateQuantity(${item.id}, 1)">+</button>
                </div>
            </div>
        `;
    });
    
    cartItems.innerHTML = html;
    cartTotal.textContent = total;
}

// Update quantity
function updateQuantity(itemId, change) {
    const item = cart.find(i => i.id === itemId);
    if (item) {
        item.quantity += change;
        if (item.quantity <= 0) {
            cart = cart.filter(i => i.id !== itemId);
        }
        updateCartCount();
        updateCartDisplay();
    }
}

// View menu
function viewMenu(restaurantId) {
    const restaurant = restaurants.find(r => r.id === restaurantId);
    const grid = document.getElementById('menu-grid');
    grid.innerHTML = '';
    
    restaurant.menu.forEach(item => {
        const card = document.createElement('div');
        card.className = 'menu-item';
        card.innerHTML = `
            <img src="${item.image}" alt="${item.name}">
            <div class="menu-item-info">
                <h4>${item.name}</h4>
                <p>₹${item.price}</p>
                <span class="type-badge ${item.type}">${item.type}</span>
                <button class="btn btn-primary" onclick="addToCart(${item.id})">Add to Cart</button>
            </div>
        `;
        grid.appendChild(card);
    });
    
    document.getElementById('menu').scrollIntoView({ behavior: 'smooth' });
}

// Checkout
function checkout() {
    if (cart.length === 0) {
        showNotification('Your cart is empty!');
        return;
    }
    
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    showNotification(`Order placed successfully! Total: ₹${total}`);
    cart = [];
    updateCartCount();
    document.getElementById('cart-modal').style.display = 'none';
}

// Show notification
function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #27ae60;
        color: white;
        padding: 15px 20px;
        border-radius: 5px;
        z-index: 1000;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Smooth scrolling for navigation
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth' });
        }
    });
});
