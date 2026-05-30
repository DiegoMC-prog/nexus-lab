export interface Permission {
    id: string;
    name: string;
    description: string;
    category: string;
}

export interface Role {
    id: string;
    name: string;
    displayName: string;
    description: string;
    color: string;
    userCount: number;
    permissions: string[];
}

export interface ApiRole {
    id: number;
    name: string;
    permissions: string[];
    users_count?: number;
}

export interface ApiRolesResponse {
    roles: ApiRole[];
}

