package com.driftbottle.app.data.model.response

import kotlinx.serialization.Serializable

@Serializable
data class LoginResponse(
    val success: Boolean,
    val message: String,
    val user: User? = null
)

@Serializable
data class User(
    val id: Int,
    val username: String,
    val gender: String,
    val points: Int,
    val experience: Int,
    val level: Int,
    val signature: String? = null,
    val is_vip: Int,
    val vip_level: Int,
    val vip_expire_date: String? = null
)
