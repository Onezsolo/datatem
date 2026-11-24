export const alertModalStore = {
    show: false,
    title: 'Notice',
    message: '',
    type: 'info',
    open(message, title, type) {
        this.message = typeof message === 'string' ? message : message || '';
        this.title = typeof title === 'string' ? title : 'Notice';
        this.type = type === 'error' || type === 'success' || type === 'info' ? type : 'info';
        this.show = true;
    },
    close() {
        this.show = false;
    },
};
