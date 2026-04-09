package com.driftbottle.app.data.repository

import androidx.datastore.core.DataStore
import androidx.datastore.preferences.core.Preferences
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.intPreferencesKey
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.core.booleanPreferencesKey
import com.driftbottle.app.data.api.DriftbottleApi
import com.driftbottle.app.data.model.request.LoginRequest
import com.driftbottle.app.data.model.request.RegisterRequest
import com.driftbottle.app.data.model.response.LoginResponse
import com.driftbottle.app.data.model.response.RegisterResponse
import kotlinx.coroutines.flow.first
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AuthRepository @Inject constructor(
    private val api: DriftbottleApi,
    private val dataStore: DataStore<Preferences>
) {
    
    companion object {
        val USER_ID = intPreferencesKey("user_id")
        val USERNAME = stringPreferencesKey("username")
        val IS_LOGGED_IN = booleanPreferencesKey("is_logged_in")
    }
    
    suspend fun login(username: String, password: String): Result<LoginResponse> {
        return try {
            val request = LoginRequest(username, password)
            val response = api.login(request)
            if (response.isSuccessful && response.body()?.success == true) {
                response.body()?.user?.let { user ->
                    saveUser(user.id, user.username)
                }
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception(response.body()?.message ?: "登录失败"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    suspend fun register(username: String, password: String, gender: String): Result<RegisterResponse> {
        return try {
            val request = RegisterRequest(username, password, gender)
            val response = api.register(request)
            if (response.isSuccessful && response.body()?.success == true) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception(response.body()?.message ?: "注册失败"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    suspend fun logout(): Result<Unit> {
        return try {
            val response = api.logout()
            if (response.isSuccessful) {
                clearUser()
                Result.success(Unit)
            } else {
                Result.failure(Exception("退出登录失败"))
            }
        } catch (e: Exception) {
            clearUser()
            Result.success(Unit)
        }
    }
    
    suspend fun isLoggedIn(): Boolean {
        return dataStore.data.first().get(IS_LOGGED_IN) ?: false
    }
    
    suspend fun getUserId(): Int? {
        return dataStore.data.first().get(USER_ID)
    }
    
    suspend fun getUsername(): String? {
        return dataStore.data.first().get(USERNAME)
    }
    
    private suspend fun saveUser(userId: Int, username: String) {
        dataStore.edit {
            it[USER_ID] = userId
            it[USERNAME] = username
            it[IS_LOGGED_IN] = true
        }
    }
    
    private suspend fun clearUser() {
        dataStore.edit {
            it.remove(USER_ID)
            it.remove(USERNAME)
            it[IS_LOGGED_IN] = false
        }
    }
}
