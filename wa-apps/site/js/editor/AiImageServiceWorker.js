self.addEventListener('activate', (event) => {
    event.waitUntil(
        self.clients.claim()
    );
});

const db_name = 'site_aiimage';
const chats_table = 'chats';
const messages_table = 'messages';
const dbRequest = indexedDB.open(db_name, 1);

const dbPromise = new Promise((resolve, reject) => {
    dbRequest.onsuccess = () => resolve(dbRequest.result);
    dbRequest.onerror = () => reject(dbRequest.error);
});

const takeResult = request =>
    new Promise((resolve, reject) => {
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });

const updateChatMessage = async ({ message_id, status, generated_images, error_message } = {}) => {
    const db = await dbPromise;
    const tx = db.transaction([chats_table, messages_table], 'readwrite');

    const messageStore = tx.objectStore(messages_table);
    const message = await takeResult(messageStore.get(message_id));
    if (!message || message.status !== 'sending') {
        return;
    }
    message.status = status;
    message.generated_images = generated_images || [];
    message.error_message = error_message || null;
    messageStore.put(message);

    const chat_id = message.chat_id;
    const chatStore = tx.objectStore(chats_table);
    const chat = await takeResult(chatStore.get(chat_id));
    if (chat.active_generations_count > 0) {
        chat.active_generations_count -= 1;
    }
    if (!chat.unread_message_ids) chat.unread_message_ids = [];
    chat.unread_message_ids.push(message_id);
    chatStore.put(chat);
};

const updateSendingHeartbeat = async (msg_id) => {
    const db = await dbPromise;
    const tx = db.transaction(messages_table, 'readwrite');
    const messageStore = tx.objectStore(messages_table);
    const message = await takeResult(messageStore.get(msg_id));
    if (!message || message.status !== 'sending') {
        return;
    }

    message.sending_heartbeat = Date.now();

    await takeResult(messageStore.put(message));
};

let hearbeat_interval_id = null;
const runSendingHeartbeatProcess = async (msg_id, timeout = 0) => {
    await updateSendingHeartbeat(msg_id);
    hearbeat_interval_id = setInterval(async () => {
        await updateSendingHeartbeat(msg_id);
    }, timeout);
};
const stopSendingHeartbeatProcess = async () => {
    if (hearbeat_interval_id) {
        clearInterval(hearbeat_interval_id);
        hearbeat_interval_id = null;
    }
};

const GENERATE_URL = '?module=editorAiImage&action=generate';
const channel = new BroadcastChannel('ai-generation-channel');
const controllers = new Map();

self.addEventListener('fetch', (event) => {
    if (!event.request.url.includes(GENERATE_URL)) {
        return;
    }
    const msg_id = event.request.headers.get('X-Message-Id');
    if (!msg_id) {
        return;
    }

    event.respondWith(handleGenerateRequest(event, msg_id));
});

self.addEventListener('message', (event) => {
    if (event.data?.type === 'SKIP_WAITING') {
        event.waitUntil(self.skipWaiting());
    }
    if (event.data?.type === 'REQUEST_ABORT') {
        const controller = controllers.get(event.data.message_id);
        if (controller) {
            controller.abort();
            controllers.delete(event.data.message_id);
        }
    }
});

async function handleGenerateRequest(event, msg_id) {
    startGeneration(msg_id);

    const controller = new AbortController();
    controllers.set(msg_id, controller);

    try {
        const response = await fetch(event.request, { signal: controller.signal });
        const response_clone = response.clone();

        // Обработку копии ответа выполняем отдельно,
        // но сообщаем Service Worker, что Promise нужно дождаться.
        event.waitUntil(handleGenerateSuccess(msg_id, response_clone));

        return response;
    } catch (error) {
        if (error.name === 'AbortError') {
            return new Response(JSON.stringify({ status: 'GENERATION_CANCELED' }),
                {
                    status: 200,
                    headers: {
                        'Content-Type': 'application/json'
                    }
                }
            );
        }
        console.error(error);
        handleGenerateError(msg_id, error);

        return new Response(JSON.stringify({ status: 'GENERATION_FAILED' }),
            {
                status: 500,
                headers: {
                    'Content-Type': 'application/json'
                }
            }
        );
    } finally {
        finishGeneration();
        if (controllers.get(msg_id) === controller) {
            controllers.delete(msg_id);
        }
    }
}

async function handleGenerateSuccess(msg_id, response) {
    const result = await response.json();
    const is_success = result?.status === 'ok';

    await delay(250);
    await updateChatMessage({
        message_id: msg_id,
        status: is_success ? 'success' : 'failed',
        error_message: is_success ? null : result?.errors?.text || null,
        generated_images: is_success ? convertGeneratedImagesToFiles(result.data || []) : [],
    });

    const type = `GENERATION_${
        is_success ? 'SUCCESS' : 'FAILED'
    }`;
    channel.postMessage({ type, message_id: msg_id });
}
async function handleGenerateError(msg_id, error) {
    await delay(250);
    await updateChatMessage({
        message_id: msg_id,
        status: 'failed',
        error_message: error.message ?? error.name ?? null,
    });

    channel.postMessage({ type: 'GENERATION_FAILED', message_id: msg_id });
}

function delay(timeout) {
    return new Promise((resolve) => {
        setTimeout(resolve, timeout);
    });
}

function startGeneration(msg_id) {
    finishGeneration();
    runSendingHeartbeatProcess(msg_id, 2000);
}

function finishGeneration() {
    stopSendingHeartbeatProcess();
}

function dataUrlToFile(data_url, filename) {
    const [header, base64] = data_url.split(',');
    const mime = header.match(/:(.*?);/)[1];
    const binary = atob(base64);
    const bytes = new Uint8Array(binary.length);

    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }

    return new File([bytes], filename, { type: mime });
};

function convertGeneratedImagesToFiles(images) {
    return images.map(image => dataUrlToFile(image.data_url, image.name));
};
