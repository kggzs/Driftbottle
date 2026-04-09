package com.driftbottle.app.domain.usecase

import com.driftbottle.app.data.repository.BottleRepository
import com.driftbottle.app.data.model.response.CreateBottleResponse
import javax.inject.Inject

class CreateBottleUseCase @Inject constructor(
    private val bottleRepository: BottleRepository
) {
    suspend operator fun invoke(
        content: String,
        isAnonymous: Boolean,
        bottleType: String = "text",
        audioFile: String? = null,
        audioDuration: Int? = null
    ): Result<CreateBottleResponse> {
        return bottleRepository.createBottle(content, isAnonymous, bottleType, audioFile, audioDuration)
    }
}
