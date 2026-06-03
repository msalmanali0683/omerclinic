import api from './api';

export const medicineDoseFromMealService = {
    getDoseFromMeals: (params) => api.get('/medicine-dose-from-meals', { params }),
    getDoseFromMeal: (id) => api.get(`/medicine-dose-from-meals/${id}`),
    createDoseFromMeal: (payload) => api.post('/medicine-dose-from-meals', payload),
    updateDoseFromMeal: (id, payload) => api.put(`/medicine-dose-from-meals/${id}`, payload),
    deleteDoseFromMeal: (id) => api.delete(`/medicine-dose-from-meals/${id}`),
    getDoseFromMealOptions: () => api.get('/medicine-dose-from-meals/options'),
};
