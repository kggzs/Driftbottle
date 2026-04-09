package com.driftbottle.app.data.model.response

import kotlinx.serialization.Serializable

@Serializable
data class CheckAuthResponse(
    val success: Boolean,
    val loggedIn: Boolean,
    val user_id: Int? = null,
    val username: String? = null,
    val gender: String? = null,
    val points: Int? = null,
    val experience: Int? = null,
    val level: Int? = null,
    val signature: String? = null,
    val is_vip: Int? = null,
    val vip_level: Int? = null,
    val daily_limits: DailyLimits? = null,
    val unread_messages: Int? = null
)

@Serializable
data class DailyLimits(
    val throw_count: Int,
    val pick_count: Int,
    val free_throws_used: Int,
    val free_throws_remaining: Int,
    val is_vip: Boolean,
    val vip_level: Int
)
