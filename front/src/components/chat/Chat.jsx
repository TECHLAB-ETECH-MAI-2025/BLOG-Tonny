import React, { useState, useEffect, useRef } from 'react';
import messageService from '../../api/message.js';
import {LoadingSpinner} from "../shared/Loader.jsx";

const ChatApp = () => {
    const [usersWithLastMessage, setUsersWithLastMessage] = useState([]);
    const [selectedUser, setSelectedUser] = useState(null);
    const [messages, setMessages] = useState([]);
    const [newMessage, setNewMessage] = useState('');
    const [searchTerm, setSearchTerm] = useState('');
    const [currentUserId, setCurrentUserId] = useState(null);
    const [loading, setLoading] = useState(false);
    const messagesEndRef = useRef(null);
    const eventSourceRef = useRef(null);

    useEffect(() => {
        const fetchInitialData = async () => {
            try {
                setLoading(true);
                const users = await messageService.getUsers();
                setUsersWithLastMessage(users.usersWithLastMessage);
                setCurrentUserId(users.current_user.id);
            } catch (error) {
                console.error('Error fetching initial data:', error);
            } finally {
                setLoading(false);
            }
        };

        fetchInitialData().then();
    }, []);

    const loadConversation = async (user) => {
        setSelectedUser(user);
        try {
            setLoading(true);
            const response = await messageService.getMessages(user.id);
            setMessages(response.messages.reverse());
        } catch (error) {
            console.error('Error loading messages:', error);
        } finally {
            setLoading(false);
        }   
    };

    const handleSendMessage = async (e) => {
        e.preventDefault();
        if (!newMessage.trim() || !selectedUser) return;

        try {
            const messageData = await messageService.sendMessage(selectedUser.id, newMessage);
            console.log('Sent message data:', messageData);

            setMessages(prevMessages => {
                if (!prevMessages.some(msg => msg.id === messageData.message.id)) {
                    return [...prevMessages, messageData.message];
                }
                return prevMessages;
            });
            setNewMessage('');
        } catch (error) {
            console.error('Error sending message:', error);
        }
    };

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        scrollToBottom();
    }, [messages]);

    useEffect(() => {
        if (selectedUser && currentUserId) {
            if (eventSourceRef.current) {
                eventSourceRef.current.close();
            }

            eventSourceRef.current = messageService.setupRealtimeConnection(
                currentUserId,
                selectedUser.id,
                (messageData) => {
                    setMessages(prevMessages => {
                        if (!prevMessages.some(msg => msg.id === messageData.id)) {
                            return [...prevMessages, messageData];
                        }
                        return prevMessages;
                    });
                }
            );
        }

        return () => {
            if (eventSourceRef.current) {
                eventSourceRef.current.close();
            }
        };
    }, [selectedUser, currentUserId]);

    const filteredUsers = usersWithLastMessage.filter(userData =>
        userData.user.username.toLowerCase().includes(searchTerm.toLowerCase())
    );

    // Fonction pour déterminer si un message est envoyé par l'utilisateur actuel
    const isCurrentUserMessage = (message) => {
        const messageUserId = String(message.sender_id || message.sender);
        const currentUserIdStr = String(currentUserId);

        return messageUserId === currentUserIdStr;
    };

    return (
        <div className="flex h-[800px] bg-gray-50">
            {/* Left Column - Conversation List */}
            <div className="w-1/3 bg-white border-r border-gray-200 flex flex-col">
                <div className="bg-white border-b border-gray-200 p-4">
                    <h1 className="text-xl font-semibold text-gray-800">Messages</h1>
                    <div className="relative mt-2">
                        <input
                            type="text"
                            placeholder="Rechercher utilisateurs..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                        />
                        <div className="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg className="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
                <div className="flex-1 overflow-y-auto">
                    {loading ? (
                        <LoadingSpinner/>
                    ) : filteredUsers.length === 0 ? (
                        <div className="p-4 text-center text-gray-500">
                            <svg className="mx-auto h-12 w-12 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <p className="text-sm">Aucune conversation</p>
                        </div>
                    ) : (
                        filteredUsers.map((userData) => (
                            <div
                                key={userData.user.id}
                                className={`cursor-pointer border-b border-gray-100 hover:bg-gray-50 transition-colors ${selectedUser?.id === userData.user.id ? 'bg-blue-50' : ''}`}
                                onClick={() => loadConversation(userData.user)}
                            >
                                <div className="flex items-center p-4">
                                    <div className="flex-shrink-0 relative">
                                        <div className="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-semibold">
                                            {userData.user.username.charAt(0).toUpperCase()}
                                        </div>
                                        {userData.user.isOnline ? (
                                            <div className="absolute -bottom-1 -right-1 h-4 w-4 bg-green-400 border-2 border-white rounded-full"></div>
                                        ) : (
                                            <div className="absolute -bottom-1 -right-1 h-4 w-4 bg-red-400 border-2 border-white rounded-full"></div>
                                        )}
                                    </div>
                                    <div className="ml-3 flex-1 min-w-0">
                                        <div className="flex justify-between items-center mb-1">
                                            <p className="text-sm font-medium text-gray-900 truncate">{userData.user.username}</p>
                                            {userData.lastMessage && (
                                                <span className="text-xs text-gray-500">
                          {new Date(userData.lastMessage.created_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}
                        </span>
                                            )}
                                        </div>
                                        {userData.lastMessage ? (
                                            <p className="text-sm text-gray-500 truncate">
                                                {userData.lastMessage.content}
                                            </p>
                                        ) : (
                                            <p className="text-sm text-gray-400 italic">Commencer une conversation</p>
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))
                    )}
                </div>
            </div>

            {/* Right Column - Chat Area */}
            <div className="flex-1 flex flex-col">
                {!selectedUser ? (
                    <div className="flex-1 flex items-center justify-center bg-gray-50">
                        <div className="text-center">
                            <svg className="mx-auto h-16 w-16 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <h3 className="text-lg font-medium text-gray-900 mb-2">Sélectionnez une conversation</h3>
                            <p className="text-gray-500">Choisissez un utilisateur dans la liste pour commencer à chatter</p>
                        </div>
                    </div>
                ) : (
                    <>
                        <div className="bg-white border-b border-gray-200 p-4 flex items-center shadow-sm">
                            <div className="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-semibold">
                                {selectedUser.username.charAt(0).toUpperCase()}
                            </div>
                            <div className="ml-3">
                                <h2 className="text-lg font-medium text-gray-800">{selectedUser.username}</h2>
                            </div>
                        </div>
                        <div className="flex-1 overflow-y-auto p-4 bg-gray-50">
                            {messages.map((message) => {
                                const isFromCurrentUser = isCurrentUserMessage(message);
                                return (
                                    <div key={message.id} className={`flex mb-4 ${isFromCurrentUser ? 'justify-end' : 'justify-start'}`}>
                                        <div className={`max-w-xs md:max-w-md rounded-2xl px-4 py-3 ${isFromCurrentUser ? 'bg-blue-500 text-white' : 'bg-white text-gray-800'} shadow-sm`}>
                                            <div className="break-words whitespace-pre-wrap">{message.content}</div>
                                            <div className={`text-xs mt-1 ${isFromCurrentUser ? 'text-blue-100' : 'text-gray-500'}`}>
                                                {new Date(message.created_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                            <div ref={messagesEndRef} />
                        </div>
                        <div className="bg-white border-t border-gray-200 p-4 shadow-inner">
                            <form onSubmit={handleSendMessage} className="flex space-x-3">
                                <input
                                    type="text"
                                    value={newMessage}
                                    onChange={(e) => setNewMessage(e.target.value)}
                                    placeholder="Tapez votre message..."
                                    className="flex-1 border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                                />
                                <button
                                    type="submit"
                                    disabled={!newMessage.trim()}
                                    className="bg-gray-600 text-white rounded-lg px-6 py-3 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed"
                                >
                                    Send
                                </button>
                            </form>
                        </div>
                    </>
                )}
            </div>
        </div>
    );
};

export default ChatApp;