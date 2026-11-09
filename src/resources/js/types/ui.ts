export type Appearance = 'light' | 'dark' | 'system';
export type ResolvedAppearance = 'light' | 'dark';

export type AppVariant = 'header' | 'sidebar';

export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
};

export interface SharedAlertProduct {
    id: number;
    title: string | null;
    nickname: string | null;
    url: string;
    domain: string;
    category: string;
    image_url: string | null;
}

export interface SharedAlertItem {
    id: number;
    type: 'price_drop' | 'back_in_stock';
    type_label: string;
    triggered_price: number;
    installment_price: number | null;
    previous_price: number | null;
    target_price: number;
    read_at: string | null;
    is_read: boolean;
    created_at: string | null;
    product: SharedAlertProduct;
}

export interface MonitoringSummary {
    monitoredCount: number;
    maxProducts: number;
    unreadAlertsCount: number;
    recentAlerts: SharedAlertItem[];
    categories: string[];
}
