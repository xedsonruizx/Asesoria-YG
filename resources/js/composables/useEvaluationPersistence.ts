import { ref, watch } from 'vue';

export function useEvaluationPersistence() {
    const STORAGE_KEY = 'evaluation_answers_yg';
    
    // Función para guardar respuestas en localStorage
    const saveAnswersToStorage = (answers: Record<number, any>, showResults: boolean) => {
        try {
            const dataToSave = {
                answers,
                timestamp: Date.now(),
                showResults
            };
            localStorage.setItem(STORAGE_KEY, JSON.stringify(dataToSave));
        } catch (error) {
            console.warn('No se pudieron guardar las respuestas:', error);
        }
    };
    
    // Función para cargar respuestas desde localStorage
    const loadAnswersFromStorage = () => {
        try {
            const stored = localStorage.getItem(STORAGE_KEY);
            if (!stored) return { answers: {}, showResults: false };
            
            const data = JSON.parse(stored);
            
            // Verificar si los datos no son muy antiguos (24 horas)
            const twentyFourHours = 24 * 60 * 60 * 1000;
            if (Date.now() - data.timestamp > twentyFourHours) {
                localStorage.removeItem(STORAGE_KEY);
                return { answers: {}, showResults: false };
            }
            
            return {
                answers: data.answers || {},
                showResults: data.showResults || false
            };
        } catch (error) {
            console.warn('Error al cargar respuestas guardadas:', error);
            localStorage.removeItem(STORAGE_KEY);
            return { answers: {}, showResults: false };
        }
    };
    
    // Función para limpiar datos guardados
    const clearStoredAnswers = () => {
        try {
            localStorage.removeItem(STORAGE_KEY);
        } catch (error) {
            console.warn('Error al limpiar respuestas guardadas:', error);
        }
    };
    
    // Función para configurar watchers automáticos
    const setupAutoSave = (answers: any, showResults: any) => {
        // Watcher para guardar automáticamente los cambios en las respuestas
        watch(answers, (newAnswers) => {
            saveAnswersToStorage(newAnswers, showResults.value);
        }, { deep: true });
        
        // Watcher para guardar automáticamente los cambios en showResults
        watch(showResults, (newShowResults) => {
            saveAnswersToStorage(answers.value, newShowResults);
        });
    };
    
    return {
        saveAnswersToStorage,
        loadAnswersFromStorage,
        clearStoredAnswers,
        setupAutoSave
    };
}