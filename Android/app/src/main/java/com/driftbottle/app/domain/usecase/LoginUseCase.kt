package com.driftbottle.app.domain.usecase

import com.driftbottle.app.data.repository.AuthRepository
import com.driftbottle.app.data.model.response.LoginResponse
import javax.inject.Inject

class LoginUseCase @Inject constructor(
    private val authRepository: AuthRepository
) {
    suspend operator fun invoke(username: String, password: String): Result<LoginResponse> {
        return authRepository.login(username, password)
    }
}
