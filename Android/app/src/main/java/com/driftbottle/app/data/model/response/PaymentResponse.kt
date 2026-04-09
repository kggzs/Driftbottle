package com.driftbottle.app.data.model.response

import kotlinx.serialization.Serializable

@Serializable
data class PaymentConfigResponse(
    val success: Boolean,
    val points_ratio: Double,
    val payment_methods: List<String>,
    val default_method: String
)

@Serializable
data class RechargeOrderResponse(
    val success: Boolean,
    val message: String,
    val order_id: String? = null,
    val pay_url: String? = null
)
