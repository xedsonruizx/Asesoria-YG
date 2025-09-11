export const getCategoryColor = (index: number): string => {
    const colors = [
        '#3B82F6', // blue
        '#10B981', // green
        '#F59E0B', // yellow
        '#EF4444', // red
        '#8B5CF6', // purple
        '#06B6D4', // cyan
        '#F97316', // orange
        '#84CC16'  // lime
    ];
    return colors[index % colors.length];
};