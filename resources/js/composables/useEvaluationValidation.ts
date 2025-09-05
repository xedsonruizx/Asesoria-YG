import { computed } from 'vue';

export function useEvaluationValidation(answers: any) {
    // Validar completitud de una sección
    const validateSection = (questionIds: number[]) => {
        const totalQuestions = questionIds.length;
        const answeredQuestions = questionIds.filter(id => 
            answers.value[id] !== undefined && answers.value[id] !== ''
        ).length;
        
        return {
            total: totalQuestions,
            answered: answeredQuestions,
            percentage: totalQuestions > 0 ? Math.round((answeredQuestions / totalQuestions) * 100) : 0,
            isComplete: answeredQuestions === totalQuestions
        };
    };
    
    // Validar respuestas requeridas
    const validateRequired = (questionIds: number[]) => {
        const missingAnswers = questionIds.filter(id => 
            answers.value[id] === undefined || answers.value[id] === ''
        );
        
        return {
            isValid: missingAnswers.length === 0,
            missingQuestions: missingAnswers,
            errorMessage: missingAnswers.length > 0 
                ? `Faltan responder ${missingAnswers.length} preguntas obligatorias`
                : null
        };
    };
    
    // Validar formato de respuestas
    const validateFormat = () => {
        const errors: string[] = [];
        
        Object.entries(answers.value).forEach(([questionId, answer]) => {
            const id = parseInt(questionId);
            
            // Validar campos numéricos
            if (id === 15) { // Años de experiencia
                if (typeof answer === 'number' && (answer < 0 || answer > 50)) {
                    errors.push('Los años de experiencia deben estar entre 0 y 50');
                }
            }
            
            // Validar campos de texto no vacíos
            if ([5, 7].includes(id)) { // Descripción de funciones, nombre
                if (typeof answer === 'string' && answer.trim().length < 2) {
                    errors.push('Los campos de texto deben tener al menos 2 caracteres');
                }
            }
            
            // Validar checkboxes
            if (id === 12) { // Temas legales
                if (Array.isArray(answer) && answer.length === 0) {
                    errors.push('Debe seleccionar al menos un tema legal de interés');
                }
            }
        });
        
        return {
            isValid: errors.length === 0,
            errors,
            errorMessage: errors.length > 0 ? errors.join('. ') : null
        };
    };
    
    // Validación general del formulario
    const isFormValid = computed(() => {
        const rrhhValidation = validateSection([1, 2, 3, 4, 5, 6, 7, 15, 16]);
        const legalValidation = validateSection([8, 9, 10, 11, 12, 13, 14]);
        const formatValidation = validateFormat();
        
        return {
            rrhh: rrhhValidation,
            legal: legalValidation,
            format: formatValidation,
            canSubmit: rrhhValidation.percentage >= 80 && legalValidation.percentage >= 80 && formatValidation.isValid,
            overallProgress: Math.round((rrhhValidation.percentage + legalValidation.percentage) / 2)
        };
    });
    
    return {
        validateSection,
        validateRequired,
        validateFormat,
        isFormValid
    };
}