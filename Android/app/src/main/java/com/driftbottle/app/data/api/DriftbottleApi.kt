package com.driftbottle.app.data.api

import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.Multipart
import retrofit2.http.POST
import retrofit2.http.Part
import retrofit2.http.Query
import okhttp3.MultipartBody
import com.driftbottle.app.data.model.request.*
import com.driftbottle.app.data.model.response.*

interface DriftbottleApi {
    
    // 用户认证
    @POST("api.php")
    suspend fun login(
        @Query("action") action: String = "login",
        @Body request: LoginRequest
    ): Response<LoginResponse>
    
    @POST("api.php")
    suspend fun register(
        @Query("action") action: String = "register",
        @Body request: RegisterRequest
    ): Response<RegisterResponse>
    
    @POST("api.php")
    suspend fun logout(
        @Query("action") action: String = "logout"
    ): Response<BaseResponse>
    
    @GET("api.php")
    suspend fun checkAuth(
        @Query("action") action: String = "check_auth"
    ): Response<CheckAuthResponse>
    
    // 漂流瓶
    @POST("api.php")
    suspend fun createBottle(
        @Query("action") action: String = "create_bottle",
        @Body request: CreateBottleRequest
    ): Response<CreateBottleResponse>
    
    @POST("api.php")
    suspend fun pickBottle(
        @Query("action") action: String = "pick_bottle"
    ): Response<PickBottleResponse>
    
    @GET("api.php")
    suspend fun getBottleDetail(
        @Query("action") action: String = "bottle_detail",
        @Query("id") bottleId: Int
    ): Response<BottleResponse>
    
    @POST("api.php")
    suspend fun commentBottle(
        @Query("action") action: String = "comment_bottle",
        @Body request: CommentRequest
    ): Response<CommentResponse>
    
    @POST("api.php")
    suspend fun likeBottle(
        @Query("action") action: String = "like_bottle",
        @Body request: LikeRequest
    ): Response<LikeResponse>
    
    @GET("api.php")
    suspend fun getUserBottles(
        @Query("action") action: String = "user_bottles",
        @Query("page") page: Int = 1,
        @Query("page_size") pageSize: Int = 10
    ): Response<BottlesResponse>
    
    @GET("api.php")
    suspend fun getUserPickedBottles(
        @Query("action") action: String = "user_picked_bottles",
        @Query("page") page: Int = 1,
        @Query("page_size") pageSize: Int = 10
    ): Response<BottlesResponse>
    
    // 系统
    @GET("api.php")
    suspend fun getAnnouncements(
        @Query("action") action: String = "get_announcements"
    ): Response<AnnouncementsResponse>
    
    @GET("api.php")
    suspend fun getBasicSettings(
        @Query("action") action: String = "get_basic_settings"
    ): Response<SettingsResponse>
    
    // 支付
    @GET("api.php")
    suspend fun getPaymentConfig(
        @Query("action") action: String = "get_payment_config"
    ): Response<PaymentConfigResponse>
    
    @POST("api.php")
    suspend fun createRechargeOrder(
        @Query("action") action: String = "create_recharge_order",
        @Body request: RechargeOrderRequest
    ): Response<RechargeOrderResponse>
    
    @Multipart
    @POST("api.php")
    suspend fun uploadAudio(
        @Query("action") action: String = "upload_audio",
        @Part audio: MultipartBody.Part
    ): Response<UploadAudioResponse>
}
