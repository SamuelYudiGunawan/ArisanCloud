export interface User {
    id: number;
    name: string;
    email: string;
}

export interface ArisanGroup {
    id: string;
    name: string;
    description: string | null;
    rekening_transfer: string | null;
    period_duration_weeks: number;
    contribution_amount: number;
    creator: User;
    member_count: number;
    is_creator: boolean;
    is_complete: boolean;
    created_at: string;
}

export interface Member {
    id: number;
    name: string;
    email: string;
    join_date: string;
}

export interface Period {
    id: string;
    period_number: number;
    start_date: string;
    end_date: string;
    status: 'pending' | 'active' | 'completed';
    all_members_paid: boolean;
    paid_count: number;
    total_members: number;
}

export interface MemberPaymentStatus {
    user_id: number;
    user_name: string;
    user_email: string;
    status: 'approved' | 'pending' | 'rejected' | 'not_paid';
    status_label: string;
    is_paid: boolean;
    payment_date: string | null;
    payment_id: string | null;
    proof_image: string | null;
}

export interface PaymentStatusResponse {
    period: Period | null;
    members: MemberPaymentStatus[];
    summary: {
        total_members: number;
        paid_count: number;
        unpaid_count: number;
        total_pot: number;
        collected: number;
    };
}

export interface Payment {
    id: string;
    user: User;
    period_id: string;
    period_number: number | null;
    amount_paid: number;
    payment_date: string;
    status: 'pending' | 'approved' | 'rejected';
    proof_image: string | null;
    notes: string | null;
    created_at: string;
}

export interface DrawHistory {
    id: string;
    period_id: string;
    period_number: number | null;
    winner: User;
    draw_date: string;
    total_pot_amount: number;
}

export interface GroupDetail extends ArisanGroup {
    members: Member[];
    active_period: Period | null;
    current_period_payment_status: MemberPaymentStatus[];
    draw_history: DrawHistory[];
    total_periods: number;
    total_draws: number;
}

export type UserPaymentStatus = 'not_paid' | 'pending' | 'approved' | 'rejected';

export interface UserPaymentHistory {
    period_number: number;
    period_name: string;
    status: UserPaymentStatus;
    status_label: string;
    payment_date: string | null;
}

