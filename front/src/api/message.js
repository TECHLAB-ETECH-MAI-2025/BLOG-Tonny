import fetchApi from './api.js';

class MessageService {
    async getUsers() {
        try {
            return await fetchApi('/chat/');
        } catch (error) {
            console.error('Failed to fetch users:', error);
            throw error;
        }
    }

    async getMessages(userId, page = 1) {
        try {
            return await fetchApi(`/chat/messages/${userId}?page=${page}`);
        } catch (error) {
            console.error('Failed to fetch messages:', error);
            throw error;
        }
    }

    async sendMessage(receiverId, content) {
        try {
            return await fetchApi('/chat/send', {
                method: 'POST',
                body: JSON.stringify({
                    receiver_id: receiverId,
                    content: content
                })
            });
        } catch (error) {
            console.error('Failed to send message:', error);
            throw error;
        }
    }


    setupRealtimeConnection(currentUserId, otherUserId, onMessageReceived) {
        const conversationTopic = `chat/conversation/${Math.min(currentUserId, otherUserId)}-${Math.max(currentUserId, otherUserId)}`;
        const mercureUrl = new URL('http://localhost:3000/.well-known/mercure');
        mercureUrl.searchParams.append('topic', conversationTopic);

        const eventSource = new EventSource(mercureUrl);

        eventSource.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                if (data.type === 'message') {
                    onMessageReceived(data.data);
                }
            } catch (error) {
                console.error('Error parsing Mercure message:', error);
            }
        };

        eventSource.onerror = (error) => {
            console.error('Mercure connection error:', error);
        };

        return eventSource;
    }
}

export default new MessageService();