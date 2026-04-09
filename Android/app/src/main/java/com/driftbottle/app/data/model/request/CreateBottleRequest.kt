package com.driftbottle.app.data.model.request

import kotlinx.serialization.Serializable

@Serializable
data class CreateBottleRequest(
    val content: String,
    val is_anonymous: Boolean,
    val bottle_type: String = "text",
    val audio_file: String? = null,
    val audio_duration: Int? = null
)
