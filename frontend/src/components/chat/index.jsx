import React, { useState, useEffect, useRef } from 'react';

const ChatApp = () => {
    const [messageHistory, setMessageHistory] = useState([]);
    const [currentMessage, setCurrentMessage] = useState('');
    const [currentChatUser, setCurrentChatUser] = useState(null);
    const [searchTerm, setSearchTerm] = useState('');
    const [isTyping, setIsTyping] = useState(false);
    const [activeChatIndex, setActiveChatIndex] = useState(-1);
    const [isSearchModalOpen, setIsSearchModalOpen] = useState(false);

    const messagesContainerRef = useRef(null);
    const textareaRef = useRef(null);
    const modalRef = useRef(null);

    const allUsers = [
        { name: 'Marie Dupont', avatar: 'MD', status: 'En ligne', avatarStyle: 'bg-purple-200 text-purple-700' },
        { name: 'Thomas Martin', avatar: 'TM', status: 'Il y a 5 min', avatarStyle: 'bg-green-200 text-green-700' },
        { name: 'Sophie Chen', avatar: 'SC', status: 'Il y a 1h', avatarStyle: 'bg-yellow-200 text-yellow-700' },
        { name: 'Alex Rivera', avatar: 'AR', status: 'Hors ligne', avatarStyle: 'bg-red-200 text-red-700' }
    ];

    const chatList = [
        { name: 'Marie Dupont', avatar: 'MD', lastMessage: 'Parfait, merci beaucoup !', avatarStyle: 'bg-purple-200 text-purple-700' },
        { name: 'Thomas Martin', avatar: 'TM', lastMessage: 'On se voit demain ?', avatarStyle: 'bg-green-200 text-green-700' },
        { name: 'Sophie Chen', avatar: 'SC', lastMessage: 'J\'ai terminé le rapport', avatarStyle: 'bg-yellow-200 text-yellow-700' }
    ];

    const getCurrentTime = () => {
        const now = new Date();
        return now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
    };

    const scrollToBottom = () => {
        if (messagesContainerRef.current) {
            messagesContainerRef.current.scrollTop = messagesContainerRef.current.scrollHeight;
        }
    };

    useEffect(() => {
        scrollToBottom();
    }, [messageHistory, isTyping]);

    // Fermer la modal quand on clique à l'extérieur
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (isSearchModalOpen && modalRef.current && !modalRef.current.contains(event.target)) {
                setIsSearchModalOpen(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, [isSearchModalOpen]);

    const handleSendMessage = () => {
        if (currentMessage.trim() === '') return;

        const newMessage = {
            type: 'user',
            content: currentMessage,
            time: getCurrentTime(),
            avatar: 'Moi',
            avatarStyle: 'bg-gray-800 text-white'
        };

        setMessageHistory(prev => [...prev, newMessage]);
        setCurrentMessage('');

        if (currentChatUser) {
            setIsTyping(true);

            setTimeout(() => {
                setIsTyping(false);
                const responses = [
                    'Ah d\'accord, je vois !',
                    'Intéressant, dis-moi en plus.',
                    'Oui, tout à fait !',
                    'Hmm, laisse-moi réfléchir...',
                    'C\'est une bonne idée ça !',
                    'Je suis d\'accord avec toi.',
                    'Ah vraiment ? Raconte !',
                    'Super, merci pour l\'info !'
                ];
                const randomResponse = responses[Math.floor(Math.random() * responses.length)];

                const responseMessage = {
                    type: 'bot',
                    content: randomResponse,
                    time: getCurrentTime(),
                    avatar: currentChatUser.avatar,
                    avatarStyle: currentChatUser.avatarStyle
                };

                setMessageHistory(prev => [...prev, responseMessage]);
            }, 1000 + Math.random() * 1000);
        }
    };

    const handleKeyPress = (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSendMessage();
        }
    };

    const selectChat = (index) => {
        setActiveChatIndex(index);
        const selectedChat = chatList[index];

        const user = allUsers.find(u => u.avatar === selectedChat.avatar) || {
            name: selectedChat.name,
            avatar: selectedChat.avatar,
            avatarStyle: selectedChat.avatarStyle,
            status: 'En ligne'
        };

        setCurrentChatUser(user);
        setMessageHistory([{
            type: 'bot',
            content: 'Salut ! Comment ça va ?',
            time: getCurrentTime(),
            avatar: user.avatar,
            avatarStyle: user.avatarStyle
        }]);
    };

    const startChatWithUser = (user) => {
        setCurrentChatUser(user);
        setActiveChatIndex(-1);
        setIsSearchModalOpen(false);
        setSearchTerm('');

        setMessageHistory([{
            type: 'bot',
            content: 'Salut ! Comment ça va ?',
            time: getCurrentTime(),
            avatar: user.avatar,
            avatarStyle: user.avatarStyle
        }]);
    };

    const startNewChat = () => {
        setCurrentChatUser(null);
        setActiveChatIndex(-1);
        setMessageHistory([]);
    };

    const filteredUsers = allUsers.filter(user =>
        user.name.toLowerCase().includes(searchTerm.toLowerCase())
    );

    const TypingIndicator = () => (
        <div className="flex items-start mb-6 animate-fadeIn">
            <div className={`w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold mr-3 flex-shrink-0 ${currentChatUser.avatarStyle}`}>
                {currentChatUser.avatar}
            </div>
            <div className="bg-white border border-gray-200 rounded-2xl rounded-bl-sm p-4 max-w-[70%]">
                <div className="flex gap-1">
                    <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{animationDelay: '0s'}}></div>
                    <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{animationDelay: '0.16s'}}></div>
                    <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{animationDelay: '0.32s'}}></div>
                </div>
            </div>
        </div>
    );

    return (
        <div className="flex h-screen max-w-6xl mx-auto bg-white shadow-xl">
            {/* Sidebar */}
            <div className="w-80 bg-white border-r border-gray-200 flex flex-col">
                <div className="p-6 border-b border-gray-200 flex justify-between items-center">
                    <h1 className="text-xl font-semibold text-gray-800">Messages</h1>
                    <button
                        onClick={() => setIsSearchModalOpen(true)}
                        className="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 hover:bg-gray-200 transition-colors"
                        title="Nouvelle discussion"
                    >
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </button>
                </div>

                <div className="flex-1 overflow-y-auto p-4">
                    {chatList.map((chat, index) => (
                        <div
                            key={index}
                            className={`flex items-center p-3 mb-1 rounded-lg cursor-pointer transition-all duration-200 text-gray-600 text-sm hover:bg-gray-50 hover:text-gray-700 ${
                                activeChatIndex === index ? 'bg-gray-50 text-gray-800 font-medium' : ''
                            }`}
                            onClick={() => selectChat(index)}
                        >
                            <div className={`w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold mr-3 flex-shrink-0 ${chat.avatarStyle}`}>
                                {chat.avatar}
                            </div>
                            <div className="flex-1 min-w-0">
                                <div className="font-medium text-inherit whitespace-nowrap overflow-hidden text-ellipsis">
                                    {chat.name}
                                </div>
                                <div className="text-xs text-gray-400 mt-1 whitespace-nowrap overflow-hidden text-ellipsis">
                                    {chat.lastMessage}
                                </div>
                            </div>
                        </div>
                    ))}
                </div>

                <div className="p-4">
                    <button
                        className="w-full p-3 bg-gray-50 border-none rounded-lg text-gray-700 font-medium cursor-pointer transition-all duration-200 hover:bg-gray-100"
                        onClick={startNewChat}
                    >
                        Réinitialiser
                    </button>
                </div>
            </div>

            {/* Modal de recherche avec fermeture au clic extérieur */}
            {isSearchModalOpen && (
                <div className="fixed inset-0 backdrop-blur-sm flex items-center justify-center z-50">
                    <div
                        ref={modalRef}
                        className="bg-white rounded-xl w-full max-w-md mx-4 p-6 shadow-lg border border-gray-200 animate-popIn"
                    >
                        <div className="flex justify-between items-center mb-4">
                            <h3 className="text-lg font-semibold">Nouvelle discussion</h3>
                            <button
                                onClick={() => setIsSearchModalOpen(false)}
                                className="text-gray-500 hover:text-gray-700"
                            >
                                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <div className="relative mb-4">
                            <svg className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <input
                                type="text"
                                className="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm bg-gray-50 text-gray-700 focus:outline-none focus:border-gray-400 focus:bg-white focus:ring-2 focus:ring-gray-100 transition-all duration-200"
                                placeholder="Rechercher des utilisateurs..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                autoFocus
                            />
                        </div>

                        <div className="max-h-80 overflow-y-auto">
                            {filteredUsers.length > 0 ? (
                                filteredUsers.map((user, index) => (
                                    <div
                                        key={index}
                                        className="flex items-center p-3 mb-1 rounded-lg cursor-pointer transition-all duration-200 hover:bg-gray-50"
                                        onClick={() => startChatWithUser(user)}
                                    >
                                        <div className={`w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold mr-3 ${user.avatarStyle}`}>
                                            {user.avatar}
                                        </div>
                                        <div className="flex-1">
                                            <div className="font-medium text-gray-800 text-sm">{user.name}</div>
                                            <div className={`text-xs mt-0.5 ${user.status === 'En ligne' ? 'text-green-500' : 'text-gray-500'}`}>
                                                {user.status}
                                            </div>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <div className="text-center py-4 text-gray-500 text-sm">
                                    Aucun utilisateur trouvé
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}

            {/* Main Chat */}
            <div className="flex-1 flex flex-col bg-white">
                {currentChatUser ? (
                    <>
                        <div className="p-6 border-b border-gray-200 bg-white flex justify-between items-center">
                            <div>
                                <div className="text-lg font-semibold text-gray-800">{currentChatUser.name}</div>
                                <div className="text-sm text-gray-500 mt-1">{currentChatUser.status}</div>
                            </div>
                            <button
                                onClick={startNewChat}
                                className="text-gray-500 hover:text-gray-700"
                                title="Fermer la conversation"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <div
                            ref={messagesContainerRef}
                            className="flex-1 overflow-y-auto p-6 bg-gray-50"
                        >
                            {messageHistory.map((message, index) => (
                                <div key={index} className={`flex items-start mb-6 animate-fadeIn ${message.type === 'user' ? 'justify-end' : ''}`}>
                                    {message.type === 'bot' && (
                                        <div className={`w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold mr-3 flex-shrink-0 ${message.avatarStyle}`}>
                                            {message.avatar}
                                        </div>
                                    )}

                                    <div className={`max-w-[70%] p-4 text-base leading-relaxed ${
                                        message.type === 'bot'
                                            ? 'bg-white text-gray-800 border border-gray-200 rounded-2xl rounded-bl-sm'
                                            : 'bg-gray-800 text-white rounded-2xl rounded-br-sm'
                                    }`}>
                                        {message.content}
                                        <div className={`text-xs mt-2 ${message.type === 'bot' ? 'text-gray-400' : 'text-gray-300'} ${message.type === 'user' ? 'text-right' : 'text-left'}`}>
                                            {message.time}
                                        </div>
                                    </div>

                                    {message.type === 'user' && (
                                        <div className="w-8 h-8 rounded-full bg-gray-800 text-white flex items-center justify-center text-xs font-semibold ml-3 flex-shrink-0">
                                            Moi
                                        </div>
                                    )}
                                </div>
                            ))}

                            {isTyping && <TypingIndicator />}
                        </div>

                        <div className="p-6 border-t border-gray-200 bg-white">
                            <div className="flex items-center bg-gray-50 border border-gray-200 rounded-xl p-1 focus-within:border-gray-400 focus-within:ring-2 focus-within:ring-gray-100 transition-all duration-200">
                <textarea
                    ref={textareaRef}
                    className="flex-1 border-none outline-none p-3.5 bg-transparent text-base text-gray-800 resize-none max-h-32 placeholder-gray-400"
                    placeholder="Tapez votre message..."
                    value={currentMessage}
                    onChange={(e) => setCurrentMessage(e.target.value)}
                    onKeyDown={handleKeyPress}
                    rows="1"
                    style={{
                        height: 'auto',
                        minHeight: '20px'
                    }}
                    onInput={(e) => {
                        e.target.style.height = 'auto';
                        e.target.style.height = Math.min(e.target.scrollHeight, 120) + 'px';
                    }}
                />
                                <button
                                    className="bg-gray-800 text-white border-none w-10 h-10 rounded-lg cursor-pointer flex items-center justify-center transition-all duration-200 hover:bg-gray-700 hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
                                    onClick={handleSendMessage}
                                    disabled={!currentMessage.trim()}
                                >
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                        <line x1="22" y1="2" x2="11" y2="13"></line>
                                        <polygon points="22,2 15,22 11,13 2,9"></polygon>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </>
                ) : (
                    <div className="flex-1 flex items-center justify-center bg-gray-50">
                        <div className="text-center text-gray-500">
                            <div className="w-16 h-16 mx-auto mb-4 bg-gray-200 rounded-full flex items-center justify-center">
                                <svg className="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                            </div>
                            <h3 className="text-lg font-medium text-gray-700 mb-2">Aucune conversation sélectionnée</h3>
                            <p className="text-sm text-gray-500 mb-4">Commencez une nouvelle discussion ou sélectionnez une conversation existante</p>
                            <button
                                className="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700 transition-colors flex items-center mx-auto"
                                onClick={() => setIsSearchModalOpen(true)}
                            >
                                <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Nouvelle discussion
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

export default ChatApp;