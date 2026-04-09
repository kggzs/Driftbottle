package com.driftbottle.app.data.model.request

import kotlinx.serialization.Serializable

@Serializable
data class CommentRequest(
    val bottle_id: Int,
    val content: String,
    val parent_id: Int? = null,
    val reply_to_user_id: Int? = null,
    val throw_back: Boolean = true
)
