package com.driftbottle.app.data.model.request

import kotlinx.serialization.Serializable

@Serializable
data class LikeRequest(
    val bottle_id: Int
)
