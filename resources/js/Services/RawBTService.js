export const printViaRawBT = async (receiptString = '') => {
    try {
        if (!receiptString || typeof receiptString !== 'string') {
            throw new Error('Receipt content is empty.');
        }

        const encodedData = encodeURIComponent(receiptString);
        const rawBtUrl = `rawbt:${encodedData}`;

        console.log('[RawBTService] Opening RawBT URL...');
        window.location.href = rawBtUrl;
    } catch (error) {
        console.error('[RawBTService] Failed to send print to RawBT:', error);
        throw error;
    }
};

export default {
    printViaRawBT,
};
