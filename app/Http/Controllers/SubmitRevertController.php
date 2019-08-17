<?php

namespace App\Http\Controllers;

use App\Jobs\RevertSubmissionJob;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\Request;
use Laravel\Lumen\Http\ResponseFactory;
use Submission\SubmissionRepository;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SubmitRevertController extends Controller
{

    /**
     * @var Dispatcher
     */
    private $dispatcher;
    /**
     * @var Request
     */
    private $request;
    /**
     * @var ResponseFactory
     */
    private $responseFactory;
    /**
     * @var SubmissionRepository
     */
    private $submissionRepository;

    public function __construct(Dispatcher $dispatcher,
                                Request $request,
                                ResponseFactory $responseFactory,
                                SubmissionRepository $submissionRepository)
    {
        $this->dispatcher = $dispatcher;
        $this->request = $request;
        $this->responseFactory = $responseFactory;
        $this->submissionRepository = $submissionRepository;
    }

    public function post()
    {
        $receipt = $this->request->get("receipt");
        $token = $this->request->get("token");

        // If either is missing, do not attempt. Any receipt without a token set shouldn't be revertable
        if (!$receipt || !$token) {
            throw new HttpException(422, "Invalid receipt/token.");
        }

        // Now attempt to fetch the submission. If receipt isn't found, or the token isn't matching, throw error
        $submission = $this->submissionRepository->getSubmission($receipt);
        if (!$submission || $submission['token'] !== $token) {
            throw new HttpException(422, "Invalid receipt/token.");
        }

        // Now check if submission has already been reverted. If so, exit gracefully
        if ($submission['removed']) {
            return $this->responseFactory->json([
                "status" => "Success",
            ]);
        }

        // If it made it this far, create the revert job. The job should do the check to ensure it only reverts after
        // submission goes through.
        $this->dispatcher->dispatch(new RevertSubmissionJob($receipt));

        return $this->responseFactory->json([
            "status" => "Success",
        ]);
    }

}
