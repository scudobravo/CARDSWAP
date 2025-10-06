import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { createRouter, createWebHistory } from 'vue-router';
import { createI18n } from 'vue-i18n';
import App from './App.vue';

// Import delle viste
import Home from './views/Home.vue';
import Login from './views/Login.vue';
import Register from './views/Register.vue';
import Categories from './views/Categories.vue';
import FootballCategory from './views/FootballCategory.vue';
import BasketballCategory from './views/BasketballCategory.vue';
import PokemonCategory from './views/PokemonCategory.vue';
import SubcategoryPage from './views/SubcategoryPage.vue';
import ProductDetail from './views/ProductDetail.vue';
import Dashboard from './views/Dashboard.vue';
import Cart from './views/Cart.vue';
import Checkout from './views/Checkout.vue';
import Orders from './views/Orders.vue';
import OrderConfirmation from './views/OrderConfirmation.vue';

// Import Account Management Views
import AccountProfile from './views/AccountProfile.vue';
import AccountAddresses from './views/AccountAddresses.vue';
import AccountPaymentMethods from './views/AccountPaymentMethods.vue';
import AccountSecurity from './views/AccountSecurity.vue';

// Import Purchases Views
import PurchasesOrders from './views/PurchasesOrders.vue';
import PurchasesWishlist from './views/PurchasesWishlist.vue';
import PurchasesHistory from './views/PurchasesHistory.vue';

// Import Sales Views
import SalesCards from './views/SalesCards.vue';
import SalesOrders from './views/SalesOrders.vue';
import SalesStatistics from './views/SalesStatistics.vue';
import SalesFeedback from './views/SalesFeedback.vue';

// Import Settings Views
import SettingsNotifications from './views/SettingsNotifications.vue';
import SettingsPrivacy from './views/SettingsPrivacy.vue';
import SettingsLanguage from './views/SettingsLanguage.vue';

// Import KYC View
import KycPage from './views/KycPage.vue';

// Import dei file di traduzione
import it from './locales/it.json';

// Configurazione delle rotte
const routes = [
    { path: '/', component: Home, name: 'home' },
    { path: '/login', component: Login, name: 'login' },
    { path: '/register', component: Register, name: 'register' },
    { path: '/categories', component: Categories, name: 'categories' },
    { path: '/categories/:id', component: Categories, name: 'category.show' },
    { path: '/category/football', component: FootballCategory, name: 'football.category' },
    { path: '/category/basketball', component: BasketballCategory, name: 'basketball.category' },
    { path: '/category/pokemon', component: PokemonCategory, name: 'pokemon.category' },
    { path: '/categories/:category/:subcategory', component: SubcategoryPage, name: 'subcategory' },
    { path: '/product/:id', component: ProductDetail, name: 'product.detail' },
    { path: '/dashboard', component: Dashboard, name: 'dashboard' },
    { path: '/cart', component: Cart, name: 'cart' },
    { path: '/checkout', component: Checkout, name: 'checkout' },
    { path: '/orders', component: Orders, name: 'orders' },
    { path: '/order-confirmation/:id', component: OrderConfirmation, name: 'order-confirmation' },
    { path: '/wishlist', component: () => import('./views/Wishlist.vue'), name: 'wishlist' },
    { path: '/chat', component: () => import('./views/Chat.vue'), name: 'chat' },
    
    // Account Management Routes
    { path: '/account/profile', component: AccountProfile, name: 'account.profile' },
    { path: '/account/addresses', component: AccountAddresses, name: 'account.addresses' },
    { path: '/account/payment-methods', component: AccountPaymentMethods, name: 'account.payment-methods' },
    { path: '/account/security', component: AccountSecurity, name: 'account.security' },
    
    // Purchases Routes
    { path: '/purchases/orders', component: PurchasesOrders, name: 'purchases.orders' },
    { path: '/purchases/wishlist', component: PurchasesWishlist, name: 'purchases.wishlist' },
    { path: '/purchases/history', component: PurchasesHistory, name: 'purchases.history' },
    
    // Sales Routes
    { path: '/sales/cards', component: SalesCards, name: 'sales.cards' },
    { path: '/sales/create', component: () => import('./views/listing/CreateListingPage.vue'), name: 'sales.create' },
    { path: '/sales/orders', component: SalesOrders, name: 'sales.orders' },
    { path: '/sales/statistics', component: SalesStatistics, name: 'sales.statistics' },
    { path: '/sales/feedback', component: SalesFeedback, name: 'sales.feedback' },
    
    // Settings Routes
    { path: '/settings/notifications', component: SettingsNotifications, name: 'settings.notifications' },
    { path: '/settings/privacy', component: SettingsPrivacy, name: 'settings.privacy' },
    { path: '/settings/language', component: SettingsLanguage, name: 'settings.language' },
    
    // KYC Route
    { path: '/kyc', component: KycPage, name: 'kyc' },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Configurazione i18n
const i18n = createI18n({
    legacy: false, // Usa Composition API mode
    locale: 'it',
    fallbackLocale: 'it',
    messages: {
        it,
    },
});

// Creazione dell'app
const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);
app.use(i18n);

app.mount('#app');
