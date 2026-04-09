package com.driftbottle.app.data.model.request

import kotlinx.serialization.Serializable

@Serializable
data class RechargeOrderRequest(
    val amount: Double,
    val payment_method: String
)
