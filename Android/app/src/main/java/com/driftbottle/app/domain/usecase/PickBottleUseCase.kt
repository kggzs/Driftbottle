package com.driftbottle.app.domain.usecase

import com.driftbottle.app.data.repository.BottleRepository
import com.driftbottle.app.data.model.response.PickBottleResponse
import javax.inject.Inject

class PickBottleUseCase @Inject constructor(
    private val bottleRepository: BottleRepository
) {
    suspend operator fun invoke(): Result<PickBottleResponse> {
        return bottleRepository.pickBottle()
    }
}
