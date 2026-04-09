package com.driftbottle.app.data.model.response

import kotlinx.serialization.Serializable

@Serializable
data class CommentResponse(
    val success: Boolean,
    val message: String,
    val comment_id: Int? = null
)

@Serializable
data class Comment(
    val id: Int,
    val bottle_id: Int,
    val user_id: Int,
    val username: String,
    val content: String,
    val parent_id: Int? = null,
    val reply_to_user_id: Int? = null,
    val reply_to_username: String? = null,
    val created_at: String,
    val replies: List<Comment> = emptyList()
)
