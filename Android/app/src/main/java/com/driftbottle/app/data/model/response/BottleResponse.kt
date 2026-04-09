package com.driftbottle.app.data.model.response

import kotlinx.serialization.Serializable

@Serializable
data class CreateBottleResponse(
    val success: Boolean,
    val message: String,
    val bottle_id: Int? = null
)

@Serializable
data class PickBottleResponse(
    val success: Boolean,
    val message: String,
    val bottle: Bottle? = null
)

@Serializable
data class BottlesResponse(
    val success: Boolean,
    val bottles: List<Bottle>,
    val total: Int,
    val page: Int,
    val page_size: Int
)

@Serializable
data class Bottle(
    val id: Int,
    val user_id: Int,
    val content: String,
    val bottle_type: String, // "text" 或 "voice"
    val audio_file: String? = null,
    val audio_duration: Int? = null,
    val is_anonymous: Boolean,
    val status: String,
    val throw_time: String,
    val user_gender: String? = null,
    val likes_count: Int = 0,
    val comments_count: Int = 0,
    val is_liked: Boolean = false
)
