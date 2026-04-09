package com.driftbottle.app.data.model.response

import kotlinx.serialization.Serializable

@Serializable
data class RegisterResponse(
    val success: Boolean,
    val message: String,
    val user_id: Int? = null
)
