import { defineStore } from "pinia";

export const useErrorStore = defineStore("error", {
    state: () => ({
        isApiDown: false,
        isDatabaseDown: false,
        isChecking: false, 
    }),
    actions: {
        setApiDown(status: boolean) {
            this.isApiDown = status;
        },
        setDatabaseDown(status: boolean) {
            this.isDatabaseDown = status;
        },
        clearErrors() {
            this.isApiDown = false;
            this.isDatabaseDown = false;
        }
    }
});