package com.driftbottle.app.data.repository

import com.driftbottle.app.data.api.DriftbottleApi
import com.driftbottle.app.data.model.request.CreateBottleRequest
import com.driftbottle.app.data.model.request.CommentRequest
import com.driftbottle.app.data.model.request.LikeRequest
import com.driftbottle.app.data.model.response.*
import okhttp3.MultipartBody
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class BottleRepository @Inject constructor(
    private val api: DriftbottleApi
) {
    
    suspend fun createBottle(
        content: String,
        isAnonymous: Boolean,
        bottleType: String = "text",
        audioFile: String? = null,
        audioDuration: Int? = null
    ): Result<CreateBottleResponse> {
        return try {
            val request = CreateBottleRequest(
                content = content,
                is_anonymous = isAnonymous,
                bottle_type = bottleType,
                audio_file = audioFile,
                audio_duration = audioDuration
            )
            val response = api.createBottle(request)
            if (response.isSuccessful) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception(response.body()?.message ?: "创建漂流瓶失败"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    suspend fun pickBottle(): Result<PickBottleResponse> {
        return try {
            val response = api.pickBottle()
            if (response.isSuccessful) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception(response.body()?.message ?: "捡漂流瓶失败"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    suspend fun getBottleDetail(bottleId: Int): Result<BottleResponse> {
        return try {
            val response = api.getBottleDetail(bottleId)
            if (response.isSuccessful) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception(response.body()?.message ?: "获取瓶子详情失败"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    suspend fun getMyBottles(page: Int = 1, pageSize: Int = 10): Result<List<BottleResponse>> {
        return try {
            val response = api.getUserBottles(page, pageSize)
            if (response.isSuccessful) {
                Result.success(response.body()?.bottles ?: emptyList())
            } else {
                Result.failure(Exception("获取我的瓶子失败"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    suspend fun getPickedBottles(page: Int = 1, pageSize: Int = 10): Result<List<BottleResponse>> {
        return try {
            val response = api.getUserPickedBottles(page, pageSize)
            if (response.isSuccessful) {
                Result.success(response.body()?.bottles ?: emptyList())
            } else {
                Result.failure(Exception("获取我捡到的瓶子失败"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    suspend fun addComment(bottleId: Int, content: String): Result<CommentResponse> {
        return try {
            val request = CommentRequest(
                bottle_id = bottleId,
                content = content,
                parent_id = null,
                reply_to_user_id = null,
                throw_back = true
            )
            val response = api.commentBottle(request)
            if (response.isSuccessful) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception(response.body()?.message ?: "评论失败"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    suspend fun commentBottle(
        bottleId: Int,
        content: String,
        parentId: Int? = null,
        replyToUserId: Int? = null,
        throwBack: Boolean = true
    ): Result<CommentResponse> {
        return try {
            val request = CommentRequest(
                bottle_id = bottleId,
                content = content,
                parent_id = parentId,
                reply_to_user_id = replyToUserId,
                throw_back = throwBack
            )
            val response = api.commentBottle(request)
            if (response.isSuccessful) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception(response.body()?.message ?: "评论失败"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    suspend fun likeBottle(bottleId: Int): Result<LikeResponse> {
        return try {
            val request = LikeRequest(bottle_id = bottleId)
            val response = api.likeBottle(request)
            if (response.isSuccessful) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception(response.body()?.message ?: "点赞失败"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    suspend fun uploadAudio(audio: MultipartBody.Part): Result<UploadAudioResponse> {
        return try {
            val response = api.uploadAudio(audio)
            if (response.isSuccessful) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception(response.body()?.message ?: "上传音频失败"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
