package com.driftbottle.app.data.model.response

import kotlinx.serialization.Serializable

@Serializable
data class LikeResponse(
    val success: Boolean,
    val message: String,
    val liked: Boolean
)
