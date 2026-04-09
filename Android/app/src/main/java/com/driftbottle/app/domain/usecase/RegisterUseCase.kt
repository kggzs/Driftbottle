package com.driftbottle.app.domain.usecase

import com.driftbottle.app.data.repository.AuthRepository
import com.driftbottle.app.data.model.response.RegisterResponse
import javax.inject.Inject

class RegisterUseCase @Inject constructor(
    private val authRepository: AuthRepository
) {
    suspend operator fun invoke(username: String, password: String, gender: String): Result<RegisterResponse> {
        return authRepository.register(username, password, gender)
    }
}
