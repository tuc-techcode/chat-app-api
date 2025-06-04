<?php
require_once __DIR__ . '/./base-repository.php';

class Conversation_Repository extends Base_Repository
{
    public function getUserConversations($user_id)
    {
        $sql = "SELECT 
            c.id AS conversation_id,
            c.name AS conversation_name,
            c.is_group,
            c.created_at AS conversation_created_at,
            last_msg.content AS last_message_content,
            last_msg.created_at AS last_message_time,
            sender.username AS last_sender_username,
            sender.avatar_url AS last_sender_avatar,
            sender.first_name AS last_sender_first_name,
            sender.last_name AS last_sender_last_name,
            (
                SELECT COUNT(*) 
                FROM messages m
                JOIN message_status ms ON m.id = ms.message_id
                WHERE m.conversation_id = c.id 
                  AND ms.user_id = :user_id_1
                  AND ms.status != 'read'
                  AND m.sender_id != :user_id_2
            ) AS unread_count,
            (
                SELECT GROUP_CONCAT(file_url)
                FROM message_attachments ma
                JOIN messages m ON ma.message_id = m.id
                WHERE m.conversation_id = c.id
                ORDER BY m.created_at DESC
                LIMIT 3
            ) AS recent_attachments
        FROM 
            conversations c
        JOIN 
            conversation_participants p ON c.id = p.conversation_id
        JOIN 
            users u ON p.user_id = u.user_id
        LEFT JOIN (  -- Changed back to LEFT JOIN to include conversations without messages
            SELECT 
                m.conversation_id,
                m.content,
                m.created_at,
                m.sender_id
            FROM 
                messages m
            WHERE 
                m.id IN (
                    SELECT MAX(id) 
                    FROM messages 
                    GROUP BY conversation_id
                )
        ) last_msg ON c.id = last_msg.conversation_id
        LEFT JOIN 
            users sender ON last_msg.sender_id = sender.user_id
        WHERE 
            c.id IN (
                SELECT conversation_id 
                FROM conversation_participants 
                WHERE user_id = :user_id_3
            )
            AND u.user_id != :user_id_4
            AND (c.is_group = 1 OR EXISTS (  -- Modified condition to include group chats regardless of messages
                SELECT 1 FROM messages 
                WHERE conversation_id = c.id
            ))
        GROUP BY 
            c.id, 
            c.name, 
            c.is_group, 
            c.created_at,
            last_msg.content,
            last_msg.created_at,
            sender.username,
            sender.avatar_url,
            sender.first_name,
            sender.last_name
        ORDER BY 
            COALESCE(last_msg.created_at, c.created_at) DESC";  // Sort by last message or conversation creation date

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id_1' => $user_id,
            'user_id_2' => $user_id,
            'user_id_3' => $user_id,
            'user_id_4' => $user_id
        ]);

        $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process and return conversations (same as before)
        return array_map(function ($conversation) use ($user_id) {
            // Get participants details
            $participantsSql = "SELECT 
                            u.user_id, 
                            u.username, 
                            u.first_name,
                            u.last_name,
                            u.avatar_url,
                            u.status,
                            u.last_seen
                          FROM conversation_participants cp
                          JOIN users u ON cp.user_id = u.user_id
                          WHERE cp.conversation_id = :conversation_id
                          AND u.user_id != :current_user_id";

            $stmt = $this->db->prepare($participantsSql);
            $stmt->execute([
                'conversation_id' => $conversation['conversation_id'],
                'current_user_id' => $user_id
            ]);

            $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format participants array
            $conversation['participants'] = array_map(function ($participant) {
                return [
                    'id' => $participant['user_id'],
                    'username' => $participant['username'],
                    'first_name' => $participant['first_name'],
                    'last_name' => $participant['last_name'],
                    'avatar' => $participant['avatar_url'],
                    'status' => $participant['status'],
                    'last_seen' => $participant['last_seen']
                ];
            }, $participants);

            // Format attachments if they exist
            if (!empty($conversation['recent_attachments'])) {
                $conversation['recent_attachments'] = explode(',', $conversation['recent_attachments']);
            } else {
                $conversation['recent_attachments'] = [];
            }

            return $conversation;
        }, $conversations);
    }

    public function getConversationMessages($conversation_id, $current_user_id, $limit = 50, $cursor = null)
    {
        $sql = "SELECT 
        m.id,
        m.content,
        m.created_at,
        m.sender_id,
        m.reply_to_id,
        u.username,
        u.avatar_url,
        u.first_name,
        u.last_name,
        -- Attachments (as JSON array string)
        (
            SELECT GROUP_CONCAT(
                JSON_OBJECT(
                    'id', ma.id,
                    'file_url', ma.file_url,
                    'file_type', ma.file_type,
                    'original_name', ma.original_name
                ) SEPARATOR '||'
            )
            FROM message_attachments ma
            WHERE ma.message_id = m.id
        ) AS attachments_json,
        -- Replied message details (flattened structure)
        IFNULL(
            (
                SELECT JSON_OBJECT(
                    'id', rm.id,
                    'content', rm.content,
                    'created_at', rm.created_at,
                    'sender_id', rm.sender_id,
                    'sender_username', ru.username,
                    'sender_first_name', ru.first_name,
                    'sender_last_name', ru.last_name,
                    'sender_avatar_url', ru.avatar_url,
                    'attachments', IFNULL(
                        (
                            SELECT CONCAT('[', GROUP_CONCAT(
                                JSON_OBJECT(
                                    'id', rma.id,
                                    'file_url', rma.file_url,
                                    'file_type', rma.file_type,
                                    'original_name', rma.original_name
                                )
                            ), ']')
                            FROM message_attachments rma
                            WHERE rma.message_id = rm.id
                        ),
                        '[]'
                    )
                )
                FROM messages rm
                JOIN users ru ON rm.sender_id = ru.user_id
                WHERE rm.id = m.reply_to_id
            ),
            NULL
        ) AS replied_message_json,
        -- Approval request (flattened structure - no nested JSON_OBJECT)
        IFNULL(
            (
                SELECT JSON_OBJECT(
                    'id', ra.id,
                    'title', ra.title,
                    'status', ra.status,
                    'priority_level', ra.priority_level,
                    'request_date', ra.request_date,
                    'approval_date', ra.approval_date,
                    'comments', ra.comments,
                    'approver_id', ra.approver_id,
                    'approver_username', au.username,
                    'approver_first_name', au.first_name,
                    'approver_last_name', au.last_name,
                    'approver_avatar_url', au.avatar_url,
                    'approver_seen_at', ra.approver_seen_at,
                    'requester_seen_at', ra.requester_seen_at
                )
                FROM request_approvals ra
                LEFT JOIN users au ON ra.approver_id = au.user_id
                WHERE ra.message_id = m.id
            ),
            NULL
        ) AS approval_request_json
    FROM 
        messages m
    JOIN 
        users u ON m.sender_id = u.user_id
    WHERE 
        m.conversation_id = :conversation_id"
            . ($cursor ? " AND m.id < :cursor" : "") . "
    ORDER BY 
        m.id DESC
    LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':conversation_id', $conversation_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);

        if ($cursor) {
            $stmt->bindParam(':cursor', $cursor, PDO::PARAM_INT);
        }

        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process messages
        return array_map(function ($message) {
            // Parse attachments
            $message['attachments'] = !empty($message['attachments_json'])
                ? array_map('json_decode', explode('||', $message['attachments_json']))
                : [];

            // Parse replied message
            $message['replied_message'] = !empty($message['replied_message_json'])
                ? json_decode($message['replied_message_json'], true)
                : null;

            // Parse approval request
            $message['approval_request'] = !empty($message['approval_request_json'])
                ? json_decode($message['approval_request_json'], true)
                : null;

            // Clean up
            unset(
                $message['attachments_json'],
                $message['replied_message_json'],
                $message['approval_request_json']
            );

            return $message;
        }, $messages);
    }


    public function getConversationDetails($conversation_id, $current_user_id)
    {
        // Get basic conversation info
        $sql = "SELECT 
            c.id AS conversation_id,
            c.name AS conversation_name,
            c.is_group,
            c.created_at AS conversation_created_at,
            (
                SELECT COUNT(*) 
                FROM conversation_participants 
                WHERE conversation_id = c.id
            ) AS participant_count
        FROM 
            conversations c
        WHERE 
            c.id = :conversation_id
            AND EXISTS (
                SELECT 1 FROM conversation_participants 
                WHERE conversation_id = c.id 
                AND user_id = :current_user_id
            )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'conversation_id' => $conversation_id,
            'current_user_id' => $current_user_id
        ]);

        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$conversation) {
            return null;
        }

        // Get participants details
        $participantsSql = "SELECT 
                        u.user_id, 
                        u.username, 
                        u.first_name,
                        u.last_name,
                        u.avatar_url,
                        u.status,
                        u.last_seen,
                        CASE
                            WHEN EXISTS (
                                SELECT 1 FROM contacts 
                                WHERE user_id = :current_user_id 
                                AND contact_id = u.user_id
                            ) THEN 1
                            ELSE 0
                        END AS is_contact
                    FROM conversation_participants cp
                    JOIN users u ON cp.user_id = u.user_id
                    WHERE cp.conversation_id = :conversation_id";

        $stmt = $this->db->prepare($participantsSql);
        $stmt->execute([
            'conversation_id' => $conversation_id,
            'current_user_id' => $current_user_id
        ]);

        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format participants array
        $conversation['participants'] = array_map(function ($participant) {
            return [
                'id' => $participant['user_id'],
                'username' => $participant['username'],
                'first_name' => $participant['first_name'],
                'last_name' => $participant['last_name'],
                'avatar' => $participant['avatar_url'],
                'status' => $participant['status'],
                'last_seen' => $participant['last_seen'],
                'is_contact' => (bool)$participant['is_contact']
            ];
        }, $participants);

        return $conversation;
    }

    public function createConversation($is_group, $name)
    {
        $sql = "INSERT INTO 
                    conversations
                    (name, is_group)
                VALUES
                    (:name, :is_group)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'name' => $name,
            'is_group' => $is_group
        ]);

        $conversationId = $this->db->lastInsertId();
        $query = $this->db->prepare("SELECT * FROM conversations WHERE id = :conversation_id");
        $query->execute([
            'conversation_id' => $conversationId
        ]);

        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getConversationById($conversation_id)
    {
        $sql = "SELECT 
                    *
                FROM
                    conversations
                WHERE id = :conversation_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'conversation_id' => $conversation_id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getDirectConversation($auth_user_id, $other_user_id)
    {
        $sql = "SELECT 
                c.*,
                u.user_id AS other_user_id,
                u.username AS other_username,
                u.avatar_url AS other_avatar_url
            FROM conversations c
            JOIN conversation_participants cp ON c.id = cp.conversation_id
            JOIN users u ON cp.user_id = u.user_id
            WHERE c.is_group = 0
            AND c.id IN (
                SELECT conversation_id 
                FROM conversation_participants 
                WHERE user_id = :auth_user_id
            )
            AND u.user_id = :other_user_id
            LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'auth_user_id' => $auth_user_id,
            'other_user_id' => $other_user_id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findDirectConversation($user1_id, $user2_id)
    {
        $sql = "SELECT c.* 
            FROM conversations c
            JOIN conversation_participants cp1 ON c.id = cp1.conversation_id
            JOIN conversation_participants cp2 ON c.id = cp2.conversation_id
            WHERE c.is_group = 0
            AND cp1.user_id = :user1_id
            AND cp2.user_id = :user2_id
            LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user1_id' => $user1_id,
            'user2_id' => $user2_id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function addConversationParticipant($conversation_id, $user_id)
    {
        $sql = "INSERT INTO conversation_participants (conversation_id, user_id) VALUES (:conversation_id, :user_id)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'conversation_id' => $conversation_id,
            'user_id' => $user_id
        ]);
    }

    public function isConversationParticipant(int $conversation_id, int $user_id): bool
    {
        $sql = "SELECT COUNT(*) 
            FROM conversation_participants 
            WHERE conversation_id = :conversation_id 
            AND user_id = :user_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'conversation_id' => $conversation_id,
            'user_id' => $user_id
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function getConversationParticipants($conversation_id)
    {
        $sql = "SELECT 
                u.user_id, 
                u.username, 
                u.first_name,
                u.last_name,
                u.avatar_url,
                u.status,
                u.last_seen,
                u.notification_token
            FROM conversation_participants cp
            JOIN users u ON cp.user_id = u.user_id
            WHERE cp.conversation_id = :conversation_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['conversation_id' => $conversation_id]);

        return array_map(function ($participant) {
            return [
                'id' => $participant['user_id'],
                'username' => $participant['username'],
                'first_name' => $participant['first_name'],
                'last_name' => $participant['last_name'],
                'avatar' => $participant['avatar_url'],
                'status' => $participant['status'],
                'last_seen' => $participant['last_seen'],
                'notification_token' => $participant['notification_token'],
            ];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function markConversationAsRead($userId, $conversationId)
    {
        $sql = "UPDATE message_status 
                 SET status = 'read' 
                 WHERE conversation_id = ? 
                 AND user_id = ?
                 AND status != 'read'";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $conversationId,
            $userId
        ]);
    }
}
