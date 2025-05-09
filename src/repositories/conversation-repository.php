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
            LEFT JOIN (
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
            GROUP BY 
                c.id, 
                c.name, 
                c.is_group, 
                c.created_at,
                last_msg.content,
                last_msg.created_at,
                sender.username,
                sender.avatar_url
            ORDER BY 
                last_msg.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id_1' => $user_id,
            'user_id_2' => $user_id,
            'user_id_3' => $user_id,
            'user_id_4' => $user_id
        ]);

        $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get participants for each conversation
        return array_map(function ($conversation) use ($user_id) {
            // Get participants details
            $participantsSql = "SELECT 
                                u.user_id, 
                                u.username, 
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
}
