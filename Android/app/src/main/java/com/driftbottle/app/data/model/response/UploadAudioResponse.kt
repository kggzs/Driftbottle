package com.driftbottle.app.data.model.response

import kotlinx.serialization.Serializable

@Serializable
data class UploadAudioResponse(
    val success: Boolean,
    val message: String,
    val file_path: String? = null,
    val duration: Int? = null
)
