export type Product = {
    id: number;
    name: string;
    sku: string;
    model: string;
    description: string;
    series: string;
    storage: string | null;
    color: string | null;
    price: number;
    stock: number;
    image: string;
    is_featured: boolean;
};

export type ProductVariant = {
    id: number;
    storage: string | null;
    color: string | null;
    price: number;
    stock: number;
};

export type CartLine = {
    id: number;
    product_id: number;
    name: string;
    model: string;
    storage: string | null;
    color: string | null;
    image: string;
    unit_price: number;
    quantity: number;
    subtotal: number;
    stock: number;
};

export type Cart = {
    items: CartLine[];
    count: number;
    total: number;
};

export type CartSummary = {
    count: number;
    total: number;
};

export type OrderItem = {
    id: number;
    product_name: string;
    quantity: number;
    price: number;
};

export type Order = {
    id: number;
    order_number: string;
    customer_name: string;
    email: string;
    phone: string;
    address: string;
    total: number;
    status: string;
    created_at: string;
    items: OrderItem[];
};

export type FilterOptions = {
    series: string[];
    storage: string[];
    color: string[];
    price_min: number;
    price_max: number;
};

export type CatalogFilters = {
    search: string | null;
    series: string | null;
    storage: string | null;
    color: string | null;
    price_min: number | null;
    price_max: number | null;
    sort: string | null;
};

export type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
};
