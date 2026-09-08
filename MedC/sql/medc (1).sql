-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 17, 2025 at 12:22 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `medc`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointment`
--

CREATE TABLE `appointment` (
  `appointment_id` int(11) NOT NULL,
  `pid` int(11) NOT NULL,
  `did` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('Pending','Confirmed','Cancelled') DEFAULT 'Pending',
  `user_id` int(11) DEFAULT NULL,
  `jitsi_meeting_link` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointment`
--

INSERT INTO `appointment` (`appointment_id`, `pid`, `did`, `appointment_date`, `appointment_time`, `status`, `user_id`, `jitsi_meeting_link`) VALUES
(4, 1, 14, '2025-02-16', '10:00:00', 'Confirmed', 4, NULL),
(5, 1, 14, '2025-02-16', '09:00:00', 'Confirmed', 4, NULL),
(6, 1, 14, '2025-02-16', '11:00:00', 'Confirmed', 4, NULL),
(7, 6, 14, '2025-02-17', '09:00:00', 'Confirmed', 15, NULL),
(8, 6, 14, '2025-02-16', '12:00:00', 'Confirmed', 15, NULL),
(9, 1, 14, '2025-02-16', '14:00:00', 'Confirmed', 4, NULL),
(10, 1, 14, '2025-02-16', '15:00:00', 'Cancelled', 4, NULL),
(11, 1, 14, '2025-02-17', '10:00:00', 'Confirmed', 4, NULL),
(12, 1, 14, '2025-02-17', '11:00:00', 'Confirmed', 4, NULL),
(13, 1, 14, '2025-02-17', '12:00:00', 'Confirmed', 4, NULL),
(14, 1, 14, '2025-02-17', '14:00:00', 'Confirmed', 4, NULL),
(15, 1, 14, '2025-02-17', '15:00:00', 'Confirmed', 4, 'https://meet.jit.si/medc_67b150520f67d'),
(16, 1, 14, '2025-02-18', '09:00:00', 'Confirmed', 4, 'https://8x8.vc/your-app-id/DoctorConsultation_16_1739865600'),
(17, 1, 14, '2025-02-18', '10:00:00', 'Confirmed', 4, 'https://meet.jit.si/medc_67b1630802ea3');

-- --------------------------------------------------------

--
-- Table structure for table `biomarker`
--

CREATE TABLE `biomarker` (
  `data_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total_cholesterol` decimal(5,2) DEFAULT NULL,
  `hdl_cholesterol` decimal(5,2) DEFAULT NULL,
  `ldl_cholesterol` decimal(5,2) DEFAULT NULL,
  `triglycerides` decimal(5,2) DEFAULT NULL,
  `systolic_blood_pressure` int(11) DEFAULT NULL,
  `diastolic_blood_pressure` int(11) DEFAULT NULL,
  `non_fasting_glucose` decimal(5,2) DEFAULT NULL,
  `glucose_post_fast` decimal(5,2) DEFAULT NULL,
  `hba1c` decimal(4,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `biomarker`
--

INSERT INTO `biomarker` (`data_id`, `user_id`, `total_cholesterol`, `hdl_cholesterol`, `ldl_cholesterol`, `triglycerides`, `systolic_blood_pressure`, `diastolic_blood_pressure`, `non_fasting_glucose`, `glucose_post_fast`, `hba1c`, `created_at`) VALUES
(1, 4, 11.00, 11.00, 11.00, 11.00, 11, 11, 11.00, 11.00, 11.00, '2025-02-13 16:08:57'),
(13, 5, 2.00, 2.00, 2.00, 2.00, 2, 2, 2.00, 2.00, 2.00, '2025-02-14 10:07:55'),
(14, 5, 1.00, 2.00, 2.00, 2.00, 2, 2, 2.00, 2.00, 2.00, '2025-02-14 10:08:21'),
(15, 4, 2.00, 2.00, 2.00, 2.00, 2, 2, 22.00, 2.00, 2.00, '2025-02-14 12:40:34'),
(16, 15, 11.00, 1.00, 1.00, 1.00, 1, 1, 1.00, 1.00, 1.00, '2025-02-15 12:57:21'),
(17, 15, 2.00, 2.00, 2.00, 2.00, 2, 2, 2.00, 2.00, 2.00, '2025-02-15 12:58:23');

-- --------------------------------------------------------

--
-- Table structure for table `communities`
--

CREATE TABLE `communities` (
  `community_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `communities`
--

INSERT INTO `communities` (`community_id`, `name`, `description`, `image`, `created_by`, `created_at`) VALUES
(1, 'Cancer Community', 'A caring space where cancer patients connect, share their journeys, and support each other.', 'INTERSTELLER.jpg', 4, '2025-02-05 20:02:50'),
(3, 'Heart Attack Community', 'A supportive space for heart attack survivors to connect and share their experiences.', '', 4, '2025-02-07 13:29:15'),
(4, 'Alzheimer Community', 'A caring community where Alzheimer\'s patients and their loved ones connect and support each other.', '', 4, '2025-02-07 13:29:15'),
(5, 'Diabetes Community', 'A supportive space where people with diabetes share experiences, tips, and encouragement.', '', 4, '2025-02-07 13:31:45'),
(6, 'Mental Health Community', 'A safe place to connect, share, and find support for mental health challenges.', '', 4, '2025-02-07 13:31:45'),
(7, 'Hypertension Community', 'A supportive space for individuals managing high blood pressure to share experiences and wellness tips.\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n', '', 4, '2025-02-07 13:32:24');

-- --------------------------------------------------------

--
-- Table structure for table `communityreports`
--

CREATE TABLE `communityreports` (
  `report_id` int(11) NOT NULL,
  `post_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disease_information`
--

CREATE TABLE `disease_information` (
  `disease_id` int(11) NOT NULL,
  `disease_name` varchar(255) NOT NULL,
  `disease_type` varchar(255) DEFAULT NULL,
  `overview` text DEFAULT NULL,
  `symptoms` text DEFAULT NULL,
  `causes` text DEFAULT NULL,
  `videos` text DEFAULT NULL,
  `infographics` text DEFAULT NULL,
  `organ_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `disease_information`
--

INSERT INTO `disease_information` (`disease_id`, `disease_name`, `disease_type`, `overview`, `symptoms`, `causes`, `videos`, `infographics`, `organ_name`) VALUES
(1, 'Anaemia', 'Other', 'Anemia is a problem of not having enough healthy red blood cells or hemoglobin to carry oxygen to the body\'s tissues. Hemoglobin is a protein found in red cells that carries oxygen from the lungs to all other organs in the body. Having anemia can cause tiredness, weakness and shortness of breath.\r
\r
There are many forms of anemia. Each has its own cause. Anemia can be short term or long term. It can range from mild to severe. Anemia can be a warning sign of serious illness.\r
\r
Treatments for anemia might involve taking supplements or having medical procedures. Eating a healthy diet might prevent some forms of anemia.', 'Anaemia can cause a range of non-specific symptoms including tiredness, weakness, dizziness or light-headedness, drowsiness, and shortness of breath, especially upon exertion. Children and pregnant women are especially vulnerable, with more severe cases of anaemia leading to an increased risk of maternal and child mortality. Iron deficiency anaemia has also been shown to affect cognitive and physical development in children and reduce productivity in adults.\r
\r
Anaemia is an indicator of both poor nutrition and poor health. It is problematic on its own, but it can also impact other global public health concerns such as stunting and wasting, low birth weight and childhood overweight and obesity due to lack of energy to exercise. School performance in children and reduced work productivity in adults due to anaemia can have further social and economic impacts for the individual and family. ', 'Anemia occurs when the blood lacks hemoglobin or red blood cells.\r
\r
This can happen if,\r
The body doesn\'t make enough hemoglobin or red blood cells.\r
Bleeding causes loss of red blood cells and hemoglobin faster than they can be replaced.\r
The body destroys red blood cells and the hemoglobin that\'s in them.', '[\"https://www.youtube.com/embed/Y-2iu2DDyxU?si=mydOxkfm_GiWveI8\",\"https://www.youtube.com/embed/9BWqjwdXQqA?si=3wwcOeq18mAfTQLY\",\"https://www.youtube.com/embed/I8dY_z_A4X4?si=6O-1CUG3_cHi4d8_\"]', '[\"06-02-2025 23-31-23.png\", \"06-02-2025 23-31-23 - Copy.png\"]', 'Heart'),
(6, 'Cholera', 'Other', 'Cholera is an acute diarrhoeal infection caused by eating or drinking food or water that is contaminated with the bacterium Vibrio cholerae. Cholera remains a global threat to public health and is an indicator of inequity and lack of social development. Researchers have estimated that every year, there are 1.3 to 4.0 million cases of cholera, and 21 000 to 143 000 deaths worldwide due to the infection.\r\n\r\nCholera is an extremely serious disease that can cause severe acute watery diarrhoea with severe dehydration. It takes between 12 hours and 5 days for a person to show symptoms after consuming contaminated food or water. Cholera affects both children and adults and can kill within hours if untreated.\r\n\r\nMost people infected with Vibrio cholerae do not develop any symptoms, although the bacteria are present in their faeces for 1-10 days after infection. This means the bacteria are shed back into the environment, potentially infecting other people.\r\n\r\nCholera is often predictable and preventable. It can ultimately be eliminated where access to clean water and sanitation facilities, as well as good hygiene practices, are ensured and sustained for the whole population', 'Cholera infection, caused by Vibrio cholerae, often shows no symptoms, but infected individuals can still spread the bacteria through contaminated water. Symptomatic cases may cause sudden, pale diarrhea that can lead to dangerous fluid loss, along with nausea and vomiting. Rapid dehydration may develop, marked by extreme thirst, dry mouth, sunken eyes, and irregular heartbeat. Severe dehydration can cause muscle cramps and, in extreme cases, life-threatening shock if untreated.', 'Cholera is caused by the bacterium Vibrio cholerae, which produces a toxin in the small intestine that triggers severe diarrhea and fluid loss. Though some infected individuals show no symptoms, they can still spread the bacteria through contaminated food or water. The primary sources of infection include contaminated surface or well water, raw or undercooked seafood, unpeeled fruits and vegetables, and improperly stored grains. Poor sanitation and crowded living conditions increase the risk of outbreaks.', '[\"https://www.youtube.com/embed/kuliQhjco9g?si=u7jPGzFB6E7mKfMy\",\"https://www.youtube.com/embed/WYwo0JvT51Y?si=tPx0Kl7pAA2vj83N\",\"https://www.youtube.com/embed/y9rTwfZ01JQ?si=5gI76UomekihCabJ\"]', '[\"4-cholera-disease-infographics-thumb.png\",\"cholera.png\"]', 'intestine'),
(7, 'Asthma', 'Other', 'Asthma is a chronic lung disease affecting people of all ages. It is caused by inflammation and muscle tightening around the airways, which makes it harder to breathe.\r\n\r\nSymptoms can include coughing, wheezing, shortness of breath and chest tightness. These symptoms can be mild or severe and can come and go over time.\r\n\r\nAlthough asthma can be a serious condition, it can be managed with the right treatment. People with symptoms of asthma should speak to a health professional.', '\r\nPeople with asthma typically experience noticeable symptoms, which often resemble those of respiratory infections. Common signs include chest tightness, pain or pressure, coughing (especially at night), shortness of breath, and wheezing. However, not all individuals with asthma will experience every symptom during each flare-up. People with chronic asthma may encounter varying symptoms at different times, and these symptoms can change between asthma attacks.', 'Several factors can increase the risk of developing asthma. Allergies are one such factor, as having allergies can raise the likelihood of developing the condition. Environmental factors also play a role; exposure to substances that irritate the airways, such as allergens, toxins, fumes, and second- or third-hand smoke, can lead to asthma. These irritants are particularly harmful to infants and young children, whose immune systems are still developing. Genetics is another important factor—if asthma or allergic diseases run in your family, your risk of developing asthma is higher. Additionally, certain respiratory infections, like respiratory syncytial virus (RSV), can damage the lungs of young children during their development.', '[\"https://www.youtube.com/embed/PzfLDi-sL3w?si=-34GWlBjcNhlnSPj\",\"\",\"\"]', '[\"Asthma.jpg\"]', 'Lungs'),
(11, 'Tonsillitis', 'Infectious', 'Tonsillitis is inflammation of the tonsils, two oval-shaped pads of tissue at the back of the throat — one tonsil on each side. Signs and symptoms of tonsillitis include swollen tonsils, sore throat, difficulty swallowing and tender lymph nodes on the sides of the neck.Most cases of tonsillitis are caused by infection with a common virus, but bacterial infections also may cause tonsillitis.Because appropriate treatment for tonsillitis depends on the cause, it\'s important to get a prompt and accurate diagnosis. Surgery to remove tonsils, once a common procedure to treat tonsillitis, is usually performed only when tonsillitis occurs frequently, doesn\'t respond to other treatments or causes serious complications.\r\n', 'Tonsillitis most commonly affects children between preschool age and the mid-teen years.Common signs and symptoms include red, swollen tonsils, sometimes with a white or yellow coating or patches. A sore throat is a frequent symptom, often making swallowing difficult or painful.Fever may accompany the infection, along with enlarged and tender lymph nodes in the neck. Some people experience a scratchy, muffled, or throaty voice.Other symptoms can include bad breath, stomachache, neck pain or stiffness, and headaches.', 'Tonsillitis is most often caused by common viruses, but bacterial infections also can be the cause.The most common bacterium causing tonsillitis is Streptococcus pyogenes (group A streptococcus), the bacterium that causes strep throat. Other strains of strep and other bacteria also may cause tonsillitis.The tonsils are the immune system\'s first line of defense against bacteria and viruses that enter your mouth. This function may make the tonsils particularly vulnerable to infection and inflammation. However, the tonsil\'s immune system function declines after puberty — a factor that may account for the rare cases of tonsillitis in adults.', '[\"https://www.youtube.com/embed/ZrvxZIrFjzQ?si=i46mBsrBuslq0rJQ\",\"https://www.youtube.com/embed/9jpMQQn3R9o?si=AtX4jeOsjxYif-tz\",\"https://www.youtube.com/embed/dIbCrv8i2mg?si=E57daIPYW8khrKSv\"]', '[\"ton.jpg\", \"ton1.jpg\"]', 'Throat'),
(12, 'Hepatitis', 'Other', 'Hepatitis is inflammation in your liver. Inflammation is your body’s response to an infection or injury. Many things can injure your liver and trigger hepatitis. Toxic chemicals, heavy substance use, viral infections and bile flow problems are a few examples. Just about any liver disease will cause hepatitis. Sometimes the injury and the inflammatory response are temporary, but sometimes they’re ongoing.\r\n\r\nShort-term liver inflammation is called acute hepatitis. It’s an immediate response to an urgent problem. Long-term liver inflammation is called chronic hepatitis. It’s a continuous response to an ongoing problem. Inflammation works to defend and repair your liver tissues from harm. But if it’s too severe or it goes on too long, inflammation itself can harm your liver and interfere with its important functions.', 'Hepatitis can be stealthy and may not cause noticeable symptoms at first. You might experience upper abdominal pain or soreness, especially on the right side. Nausea or loss of appetite can also occur. Fatigue and a general feeling of unwellness are common. In cases of viral infection, fever may be present.', 'In more severe cases of acute hepatitis and long-term chronic hepatitis, the liver may struggle to process bile properly. As a result, bile overflows into the bloodstream instead of reaching its intended destination. This can lead to jaundice, causing a yellow tint to the skin and eyes. Dark-colored urine and light-colored stool may also occur. Additionally, individuals may experience pruritus (itchy skin) and symptoms of hepatic encephalopathy, such as confusion, disorientation, or drowsiness.', '[\"https://www.youtube.com/embed/IkTPnzbTg88?si=Nam-vFHkN_pi_UEF\",\"https://www.youtube.com/embed/jVz78aTWgpw?si=YZvocFk4mhW_ankz\",\"https://youtube.com/shorts/rFUASGLlIBo?si=dI6_oyHbPrZxbDCF\"]', '[\"hip.jpg\",\"hip1.jpg\"]', 'Liver'),
(13, 'Presbycusis (Age-Related Hearing Loss)', 'Other', 'Presbycusis refers to bilateral age-related hearing loss. In literal terms, presbycusis means \"old hearing\" or \"elder hearing.\"It becomes noticeable around age 60 and progresses slowly; however, there is evidence that certain stressors can speed the rate of deterioration. The diagnosis can be confirmed with audiometry.The hallmark of presbycusis is the impaired ability to understand high-frequency components of speech (voiceless consonants, such as p, k, f, s, and ch).There is no cure; however, hearing aids that amplify sounds can be used to mitigate symptoms. Anatomically, presbycusis involves multiple components of the auditory system. It is primarily due to age-related changes in hair cells, the stria vascularis, and afferent spiral ganglion neurons. During the normal hearing, sound, in the form of air vibration, is captured by the funnel-shaped external ear and is directed to the tympanic membrane. This causes the tympanic membrane to vibrate at a specific frequency and amplitude. This movement is amplified by three small bones in the middle ear: the malleus, incus, and stapes. From there, the signal proceeds as vibrations that are transmitted through the fluid within the inner ear to the cochlea. In the cochlea, receptors known as hair cells transform the information encoded in the vibrations into a neurologic signal which travels to the auditory cortex via the cochlear nerve.', 'Each person’s symptoms may vary, but common signs of age-related hearing loss include difficulty understanding speech, making it sound mumbled or slurred. Many people struggle to hear high-pitched sounds and find it hard to follow conversations, especially in noisy environments, with men\'s voices often being easier to hear than women\'s. Certain sounds may seem excessively loud and irritating, and a persistent ringing sound (tinnitus) may occur in one or both ears. The symptoms of age-related hearing loss can sometimes resemble those of other health conditions.', 'Age-related hearing loss can have multiple causes, most commonly due to changes within the inner ear, but it can also result from changes in the middle ear or along the nerve pathways to the brain. Several factors contribute to this type of hearing loss, including long-term exposure to loud noises, such as music or workplace noise, and the loss of hair cells, which are sensory receptors in the inner ear. Genetics may also play a role, along with natural aging and certain health conditions like heart disease or diabetes. Additionally, some medications, including aspirin, chemotherapy drugs, and certain antibiotics, may cause hearing loss as a side effect. Other contributing factors include infections, smoking, lower income levels, and being white.', '[\"https://www.youtube.com/embed/_kUy4p87_nk?si=EqzHCR74Kjaek6dj\",\"https://www.youtube.com/embed/dA9H8yRv0Pg?si=c1TZLTQfr68v7yQi\",\"https://www.youtube.com/embed/x6mGyTjg9cE?si=p4B-kcRsBjM2Imzh\"]', '[\"ear.jpg\",\"ear1.jpg\"]', 'Ear'),
(14, 'Alzheimer', 'Other', 'Alzheimer\'s disease is the most common cause of dementia. Alzheimer\'s disease is the biological process that begins with the appearance of a buildup of proteins in the form of amyloid plaques and neurofibrillary tangles in the brain. This causes brain cells to die over time and the brain to shrink.\r\n\r\nAbout 6.9 million people in the United States age 65 and older live with Alzheimer\'s disease. Among them, more than 70% are age 75 and older. Of the more than 55 million people in the world with dementia, 60% to 70% are estimated to have Alzheimer\'s disease.\r\n\r\nEarly symptoms of Alzheimer\'s disease include forgetting recent events or conversations. Over time, Alzheimer\'s disease leads to serious memory loss and affects a person\'s ability to do everyday tasks.\r\n\r\nThere is no cure for Alzheimer\'s disease. In advanced stages, loss of brain function can cause dehydration, poor nutrition or infection. These complications can result in death.\r\n\r\nBut medicines may improve symptoms or slow the decline in thinking. Programs and services can help support people with the disease and their caregivers.', 'Memory loss is the key symptom of Alzheimer\'s disease. Early in the disease, people may have trouble remembering recent events or conversations. Over time, memory gets worse and other symptoms occur.\r\n\r\nAt first, someone with the disease may be aware of having trouble remembering things and thinking clearly. As signs and symptoms get worse, a family member or friend may be more likely to notice the issues.\r\n\r\nBrain changes from Alzheimer\'s disease lead to the following symptoms that get worse over time.', 'The exact causes of Alzheimer\'s disease aren\'t fully understood. But at a basic level, brain proteins don\'t function as usual. This disrupts the work of brain cells, also known as neurons, and triggers a series of events. The neurons become damaged and lose connections to each other. They eventually die.\r\n\r\nScientists believe that for most people, Alzheimer\'s disease is caused by a combination of genetic, lifestyle and environmental factors that affect the brain over time. In less than 1% of people, Alzheimer\'s is caused by specific genetic changes that almost guarantee a person will develop the disease. For people in this group, the disease usually begins in middle age.', '[\"https://www.youtube.com/embed/zTd0-A5yDZI?si=I_SrAxu3O_4ceHfy\",\"https://www.youtube.com/embed/oT5pDvdMzhk?si=k9MyiBE6ZSk-RRU7\",\"https://www.youtube.com/embed/wfLP8fFrOp0?si=KI5cEW3TVrKIKq30\"]', '[\"alz2.jpg\",\"alz3.jpg\"]', 'Brain'),
(15, 'Dengue', 'Infectious', 'Dengue (DENG-gey) fever is a mosquito-borne illness that occurs in tropical and subtropical areas of the world. Mild dengue fever causes a high fever and flu-like symptoms. The severe form of dengue fever, also called dengue hemorrhagic fever, can cause serious bleeding, a sudden drop in blood pressure (shock) and death.\r\n\r\nMillions of cases of dengue infection occur worldwide each year. Dengue fever is most common in Southeast Asia, the western Pacific islands, Latin America and Africa. But the disease has been spreading to new areas, including local outbreaks in Europe and southern parts of the United States.\r\n\r\nResearchers are working on dengue fever vaccines. For now, in areas where dengue fever is common, the best ways to prevent infection are to avoid being bitten by mosquitoes and to take steps to reduce the mosquito population.', 'Many people with dengue infection experience no symptoms. When symptoms do occur, they may be mistaken for the flu and typically begin four to ten days after a mosquito bite. Dengue fever causes a high fever of 104°F (40°C) and may include symptoms such as headache, muscle, bone, or joint pain, nausea, vomiting, pain behind the eyes, swollen glands, and a rash.', 'Dengue fever is caused by any one of four types of dengue viruses. You can\'t get dengue fever from being around an infected person. Instead, dengue fever is spread through mosquito bites.\r\n\r\nThe two types of mosquitoes that most often spread the dengue viruses are common both in and around human lodgings. When a mosquito bites a person infected with a dengue virus, the virus enters the mosquito. Then, when the infected mosquito bites another person, the virus enters that person\'s bloodstream and causes an infection.', '[\"https://www.youtube.com/embed/hFuDs74NPc4?si=5FRMwL5LpJ9wuArT\",\"https://www.youtube.com/embed/P-_hqG9HKj8?si=br_wU8w6ViXwJDFp\",\"https://www.youtube.com/embed/51AECqscavc?si=24lRs4ysbBUFawUo\"]', '[\"dengue_img1.png\",\"dengue_img2.png\"]', '-'),
(16, 'Kidney stones (Nephrolithiasis)', 'Other', 'Kidney stones are solid masses or crystals that form from substances (like minerals, acids and salts) in your kidneys. They can be as small as a grain of sand or — rarely — larger than a golf ball. Kidney stones are also called renal calculi or nephrolithiasis.\r\n\r\nDepending on the size of your kidney stone (or stones), you may not even realize that you have one. Smaller stones can pass through your urinary tract in your pee with no symptoms. Large kidney stones can get trapped in your ureter (the tube that drains urine from your kidney down to your bladder). This can cause pee to back up and limit your kidney’s ability to filter waste from your body. It can also cause bleeding.\r\n\r\nIt can take as long as three weeks for kidney stones to pass on their own. Even some small stones can cause extreme pain as they go through your urinary tract and out of your body. You may need a provider to break up and remove a stone that can’t pass on its own.\r\n\r\nHow common are kidney stones?\r\nAbout 1 in 10 people will get a kidney stone during their lifetime. They’re most common in people assigned male at birth (AMAB) in their 30s and 40s. They’re also more common among non-Hispanic white people.', 'The most common symptom of kidney stones is pain in the lower back, belly, or side, known as flank pain, which may extend from the groin to the side. This pain can be dull or sharp and severe, often worsening in waves, earning it the term \"colicky pain.\" Other symptoms include nausea, vomiting, bloody urine, pain or difficulty while urinating, frequent urge to urinate, fever, chills, and cloudy or foul-smelling urine. Smaller kidney stones may not cause any pain or noticeable symptoms.', 'Your pee contains minerals, acids and other substances, like calcium, sodium, oxalate and uric acid. When you have too many particles of these substances in your pee and too little liquid, they can start to stick together, forming crystals or stones. Kidney stones can form over months or years.Calcium-oxalate and calcium phosphate stones. Calcium-based stones can form when you eat high-oxalate or low-calcium foods and aren’t drinking enough fluids. Calcium-oxalate stones are the most common type of kidney stones.', '[\"https://www.youtube.com/embed/DK9AkAVDoho?si=E5MghAkTU4yiDC0V\",\"https://www.youtube.com/embed/W0GpIMNTPYg?si=YZElXM5ewq4Ic1Bk\",\"https://www.youtube.com/embed/znjTv0JYH0Y?si=ooBEZ8Pgk-cLqzpF\"]', '[\"kidney.jpg\",\"kidney1.jpg\"]', 'Kidney'),
(17, 'Pancreatic Cancer ', 'Other', 'Pancreatic cancer occurs when cells in your pancreas mutate (change) and multiply out of control, forming a tumor. Your pancreas is a gland in your abdomen (belly), between your spine and stomach. It makes hormones that control blood-sugar levels and enzymes that aid in digestion.\r\n\r\nMost pancreatic cancers start in the ducts of your pancreas. The main pancreatic duct (the duct of Wirsung) connects your pancreas to your common bile duct.\r\n\r\nEarly-stage pancreatic tumors don’t show up on imaging tests. For this reason, many people don’t receive a diagnosis until the cancer has spread (metastasis). Pancreatic cancer is also resistant to many common cancer drugs, making it notoriously difficult to treat.\r\n\r\nOngoing research focuses on early detection through genetic testing and new imaging methods. Still, there’s much to learn.', 'Pancreatic cancer usually has no early signs, and symptoms typically appear once the tumor affects other organs in the digestive system. Common symptoms include jaundice, dark urine, light-colored stool, upper abdominal and middle back pain, fatigue, itchy skin, nausea, vomiting, gas, bloating, and loss of appetite. Unexplained weight loss, blood clots, and new-onset diabetes may also occur.', 'It\'s not clear what causes pancreatic cancer. Doctors have found some factors that might raise the risk of this type of cancer. These include smoking and having a family history of pancreatic cancer.The pancreas is about 6 inches (15 centimeters) long and looks something like a pear lying on its side. It releases hormones, including insulin. These hormones help the body process the sugar in the foods you eat. The pancreas also makes digestive juices to help the body digest food and take in nutrients.', '[\"https://www.youtube.com/embed/kYgsCfgeGX4?si=ynzwRmkhq5D516kM\",\"https://www.youtube.com/embed/QUWOh-zS4eo?si=Y1n2T5YWR1oq_7Ph\",\"https://www.youtube.com/embed/MHO2nX0udhQ?si=_RaiqzQSeZ1g0TKU\"]', '[\"pan.png\",\"pan1.png\"]', 'Pancreas'),
(20, 'Bladder cancer', NULL, 'Bladder cancer is a common type of cancer that begins in the cells of the bladder. The bladder is a hollow muscular organ in your lower abdomen that stores urine.\r\n\r\nBladder cancer most often begins in the cells (urothelial cells) that line the inside of your bladder. Urothelial cells are also found in your kidneys and the tubes (ureters) that connect the kidneys to the bladder. Urothelial cancer can happen in the kidneys and ureters, too, but it\'s much more common in the bladder.\r\n\r\nMost bladder cancers are diagnosed at an early stage, when the cancer is highly treatable. But even early-stage bladder cancers can come back after successful treatment. For this reason, people with bladder cancer typically need follow-up tests for years after treatment to look for bladder cancer that recurs.\r\n', '\r\nThe most common symptoms of pink eye include redness and itchiness in one or both eyes, often causing irritation and discomfort. Many individuals experience a gritty sensation, making it feel as if there is something in the eye. A discharge may also be present, which can form a crust overnight and make it difficult to open the eyes in the morning. Excessive tearing is another common symptom as the eyes try to flush out irritants. Sensitivity to light, known as photophobia, may develop, making bright lights or screens uncomfortable.', 'Bladder cancer signs and symptoms may include blood in the urine, known as hematuria, which can make the urine appear bright red or cola-colored, though in some cases, the urine may look normal, and blood is only detected through a lab test. Frequent urination is another common symptom, as the bladder becomes more irritated or affected by the disease. Many individuals also experience painful urination, which can cause significant discomfort. In some cases, back pain may develop, especially if the cancer has spread beyond the bladder.', '[\"https://www.youtube.com/embed/7N6fJQkCGZw?si=ApX5ZRk6p3YxlFzD\",\"https://www.youtube.com/embed/f5vmjOsxPYA?si=5D7NyIO5VIM7EQBQ\",\"https://www.youtube.com/embed/U0XdYXWyBvQ?si=oPxX-_XZ1TFfinwq\"]', '', 'Bladder'),
(21, 'Conjunctivitis (Pink Eye)', NULL, 'Pink eye is an inflammation of the transparent membrane that lines the eyelid and eyeball. This membrane is called the conjunctiva. When small blood vessels in the conjunctiva become swollen and irritated, they\'re more visible. This is what causes the whites of the eyes to appear reddish or pink. Pink eye also is called conjunctivitis.\r
\r
Pink eye is most often caused by a viral infection. It also can be caused by a bacterial infection, an allergic reaction or — in babies — an incompletely opened tear duct.\r
\r
Though pink eye can be irritating, it rarely affects your vision. Treatments can help ease the discomfort of pink eye. Because pink eye can be contagious, getting an early diagnosis and taking certain precautions can help limit its spread.', 'The most common symptoms of pink eye include redness and itchiness in one or both eyes, often causing significant discomfort. Many individuals experience a gritty sensation, making it feel as if something is stuck in the eye. A discharge, which may be watery or pus-like, can form a crust overnight, making it difficult to open the eyes in the morning. Excessive tearing is also common as the eyes try to flush out irritants. Some people develop sensitivity to light, known as photophobia, making bright lights or screens uncomfortable. The severity of these symptoms can vary depending on whether the cause is viral, bacterial, or due to allergies.', 'Causes of pink eye include viruses and bacteria, which are the most common infectious sources of the condition. Allergies can also trigger pink eye, especially due to exposure to pollen, pet dander, or dust. In some cases, a chemical splash in the eye from substances like smoke, chlorine, or cleaning products can lead to irritation and inflammation. Additionally, a foreign object in the eye, such as dirt or debris, may cause redness and discomfort. In newborns, a blocked tear duct can result in pink eye-like symptoms due to improper tear drainage, leading to bacterial buildup and infection.', '[\"https://www.youtube.com/embed/e-xRLKXSV4s?si=qibhiDWoBHXeTZ1u\",\"https://www.youtube.com/embed/7dls5xV1HCU?si=8WNFgoNFnVo99SZ9\",\"https://www.youtube.com/embed/bluxsBOFfrY?si=GINepjLCShSQwHma\"]', '[\"conj.png\",\"conj1.png\"]', 'Eye'),
(22, 'Eye cancer', NULL, 'Eye cancer includes several rare types of cancers that begin in your eye, including your eyeball and the structures surrounding your eyeball. Eye cancer starts when cells multiply out of control and form a tumor. Tumors can be benign (noncancerous) or malignant (cancerous). Unlike benign tumors, malignant tumors can grow and the cancer can spread throughout your body. Diagnosing and treating eye cancers early can often prevent the spread.\r\nEye cancer includes several rare types of cancers that begin in your eye, including your eyeball and the structures surrounding your eyeball. Eye cancer starts when cells multiply out of control and form a tumor. Tumors can be benign (noncancerous) or malignant (cancerous). Unlike benign tumors, malignant tumors can grow and the cancer can spread throughout your body.\r\nDiagnosing and treating eye cancers early can often prevent the spread.', 'The most common symptom of eye cancer is painless vision loss, which may occur gradually or suddenly. Other vision-related signs include blurry vision, partial or total vision loss, and seeing flashes of light, squiggly lines, or spots known as floaters. In addition to vision problems, some individuals may experience a bulging eye, persistent eye irritation that does not improve, or a dark spot on the iris that gradually increases in size. A growing lump on the eyelid or within the eyeball can also be a warning sign. Changes in the positioning or movement of the eyeball within the socket may further indicate the presence of eye cancer, making early detection and medical evaluation essential.', 'As with cancers in general, eye cancer occurs when cells begin to divide and multiply out of control, eventually forming a mass called a tumor. Pieces of the tumor can break off and spread to your lymph nodes and bloodstream. The cancer cells can travel to other parts of your body via your bloodstream and lymphatic system, causing new tumors to form in other organs. When this happens, healthcare providers say that your cancer has “spread” or “metastasized.” It’s a sign of a more advanced disease.\r\nScientists are still researching to understand what causes otherwise healthy cells to become cancer cells.', '[\"https://www.youtube.com/embed/4DR5o6FAz5I?si=r7175LLDrFNdA_fL\",\" https://www.youtube.com/embed/jQi-EYHfNuw?si=vzTIaitk1TBwHK1N\",\"https://www.youtube.com/embed/AJS5VrGnhZA?si=G50z70yCzkhtNWtm\"]', '[\"INTERSTELLER.jpg\",\"eye_cancer.jpg\"]', 'Eye'),
(23, 'Gastritis', NULL, 'Gastritis is a general term for a group of conditions with one thing in common: Inflammation of the lining of the stomach. The inflammation of gastritis is most often the result of infection with the same bacterium that causes most stomach ulcers or the regular use of certain pain relievers. Drinking too much alcohol also can contribute to gastritis.\r\n\r\nGastritis may occur suddenly (acute gastritis) or appear slowly over time (chronic gastritis). In some cases, gastritis can lead to ulcers and an increased risk of stomach cancer. For most people, however, gastritis isn\'t serious and improves quickly with treatment.', 'Gastritis does not always cause symptoms, but when it does, individuals may experience a gnawing or burning ache in the upper belly, commonly referred to as indigestion. This discomfort can either worsen or improve after eating, depending on the underlying cause. Nausea is another common symptom, often accompanied by vomiting in more severe cases. ', 'Gastritis does not always cause symptoms, but when it does, individuals may experience a gnawing or burning ache in the upper belly, commonly referred to as indigestion. This discomfort can either worsen or improve after eating, depending on the underlying cause. Nausea is another common symptom, often accompanied by vomiting in more severe cases. ', '[\"https://www.youtube.com/embed/FXlzv9cVVo8?si=aJh3W6KbG3T4edB3\",\"https://www.youtube.com/embed/LUssH0kxFdQ?si=XZKxkAoA8cekg8fA\",\"https://www.youtube.com/embed/NexLWU56H8M?si=sx1XDLDz6WAwWkOG\"]', '[\"gas1.jpg\",\"gas.jpg\"]', 'Stomach'),
(24, 'Fabry disease', NULL, 'Fabry disease is a rare genetic condition in which you don’t produce enough healthy versions of an enzyme called alpha-galactosidase A (alpha-GAL). This enzyme breaks down sphingolipids, a fat-like substance, and prevents them from collecting in your blood vessels and tissues. Fabry disease is a type of lysosomal storage disorder.\r\n\r\nWithout functioning alpha-GAL enzymes, harmful levels of sphingolipids build up in your blood vessels and tissues. Fabry disease affects your heart, kidneys, brain, central nervous system and skin.\r\n\r\nOther names for the condition are Anderson-Fabry disease, Fabry’s disease and alpha-galactosidase-A deficiency.\r\n\r\nTypes of Fabry disease\r\nThe types of Fabry disease reflect the age when symptoms first appear. Types include:\r\n\r\nClassic type: Symptoms of classic Fabry disease appear during childhood or the teenage years. One common disease symptom — a painful burning sensation in your hands and feet — may be noticeable as early as age 2. Symptoms get progressively worse over time.\r\nLate-onset/atypical type: People with late-onset Fabry disease don’t have symptoms until they’re in their 30s or older. The first indication of a problem may be kidney failure or heart disease.\r\nHow common is Fabry disease?\r\nApproximately 1 out of every 40,000 men have classic Fabry disease. Late-onset or atypical Fabry disease is more common. It affects about 1 in every 1,500 to 4,000 men.\r\n\r\nExperts aren’t sure how many women have Fabry disease. Some women don’t have symptoms or have mild, easy-to-dismiss symptoms, so the condition frequently goes undiagnosed.', 'Fabry disease symptoms vary depending on the type, with some being mild and appearing later in life. Men often experience more severe symptoms than women. Common symptoms include numbness, tingling, burning, or pain in the hands and feet, along with extreme pain during physical activity. Many individuals develop heat or cold intolerance, dizziness, and flu-like symptoms such as fatigue, fever, and body aches. Eye abnormalities like cornea verticillata, which do not affect vision, can be detected during an eye exam. Gastrointestinal issues, including diarrhea, constipation, and abdominal pain, are also common. ', 'A genetic mutation of the galactosidase alpha (GLA) gene causes Fabry disease. The GLA gene produces the alpha-GAL enzyme that helps break down fatty substances (sphingolipids). People who inherit a defective GLA gene don’t produce enough alpha-GAL enzyme. As a result, fatty substances build up in their blood vessels.', '[\"https://www.youtube.com/embed/ZjfiQbkTpc4?si=t8X7nySj2WdCHyPl\",\"https://www.youtube.com/embed/asq9-CCulFQ?si=XcVEs5G5uHNT6pKI\",\"https://www.youtube.com/embed/hEge4B86x6o?si=XFLGW7EDq-Oc5wSy\"]', '[\"fabry1.jpg\",\"fabry.jpg\"]', 'Heart'),
(25, 'Bone cancer', NULL, 'Bone cancer is a growth of cells that starts in a bone. Bone cancer can start in any bone. But it most often affects the thighbone.\r\n\r\nThe term \"bone cancer\" doesn\'t include cancer that starts in another part of the body and spreads to the bones. Instead, cancer that spreads to the bone is named for the place it began. For example, cancer that starts in the lungs and spreads to the bones is still lung cancer. Healthcare professionals might call it lung cancer that has metastasized to the bones.\r\n\r\nCancer that starts in the bones is rare. Different types of bone cancers exist. Some types of bone cancers mostly happen in children. Other types happen mostly in adults.\r\n\r\nCommon bone cancer treatments include surgery, radiation and chemotherapy. The best treatment for your bone cancer depends on the type of bone cancer, which bone is affected and other factors.', 'Signs and symptoms of bone cancer include persistent bone pain, which may worsen over time. Swelling and tenderness can develop near the affected area, sometimes making movement difficult. The disease can weaken bones, increasing the risk of fractures even with minor trauma. Additionally, individuals with bone cancer may experience extreme fatigue and unexplained weight loss, which can further impact overall health and well-being.', 'The cause of most bone cancers isn\'t known. Bone cancer starts when cells in or near a bone develop changes in their DNA. A cell\'s DNA holds the instructions that tell the cell what to do. In healthy cells, the DNA gives instructions to grow and multiply at a set rate. The instructions tell the cells to die at a set time. In cancer cells, the DNA changes give different instructions. The changes tell the cancer cells to make many more cells quickly. Cancer cells can keep living when healthy cells would die. This causes too many cells.', '[\"https://www.youtube.com/embed/UnBAbOimdJc?si=oMymd-5y5O4odsFl\",\"https://www.youtube.com/embed/YidmOOem3WA?si=4AAILBbEwMNdWUqC\",\"https://www.youtube.com/embed/SQzmqcwIkkE?si=F4orcmmXHnJ3qRaB\"]', '[\"alz2.jpg\",\"alz3.jpg\"]', 'Bone'),
(26, 'Brain tumours', NULL, 'A brain tumor is a growth of cells in the brain or near it. Brain tumors can happen in the brain tissue. Brain tumors also can happen near the brain tissue. Nearby locations include nerves, the pituitary gland, the pineal gland, and the membranes that cover the surface of the brain.\r\n\r\nBrain tumors can begin in the brain. These are called primary brain tumors. Sometimes, cancer spreads to the brain from other parts of the body. These tumors are secondary brain tumors, also called metastatic brain tumors.\r\n\r\nMany different types of primary brain tumors exist. Some brain tumors aren\'t cancerous. These are called noncancerous brain tumors or benign brain tumors. Noncancerous brain tumors may grow over time and press on the brain tissue. Other brain tumors are brain cancers, also called malignant brain tumors. Brain cancers may grow quickly. The cancer cells can invade and destroy the brain tissue.', 'A brain tumor is a growth of abnormal cells in or near the brain, including areas like nerves, the pituitary gland, and the membranes covering the brain. Tumors can be primary, meaning they originate in the brain, or secondary (metastatic), meaning they spread from other parts of the body. There are noncancerous (benign) tumors, which grow slowly and may press on brain tissue, and cancerous (malignant) tumors, which grow quickly and invade nearby brain structures. While benign tumors are not cancerous, they can still cause serious health problems depending on their size and location. Malignant tumors, on the other hand, can spread rapidly and be life-threatening. The symptoms of brain tumors vary depending on their type, size, and location, potentially affecting brain function, nerves, and overall health.', 'Brain tumors that start as a growth of cells in the brain are called primary brain tumors. They might start right in the brain or in the tissue nearby. Nearby tissue might include the membranes that cover the brain, called meninges. Brain tumors also can happen in nerves, the pituitary gland and the pineal gland.\r\n\r\nBrain tumors happen when cells in or near the brain get changes in their DNA. A cell\'s DNA holds the instructions that tell the cell what to do. The changes tell the cells to grow quickly and continue living when healthy cells would die as part of their natural life cycle. This makes a lot of extra cells in the brain. The cells can form a growth called a tumor.', '[\"https://www.youtube.com/embed/uyKQnMX2ggA?si=aG-PvQIarbcKex7W\",\"https://www.youtube.com/embed/MxhCGARAyrY?si=hqRWvxvTIeKL6Qwv\",\"https://www.youtube.com/embed/cSeXJKSQpiI?si=u_zeLQ2EdIU0AzVT\"]', '[\"brain_tumor1.jpg\",\"brain_tumor.jpg\"]', 'Brain'),
(27, 'Chickenpox', NULL, 'Chickenpox is an infection that causes an itchy, blister-like skin rash. A virus called varicella-zoster causes it. Chickenpox is highly contagious. But it’s much less common today because there’s a vaccine that protects you from it. Children are the most susceptible to getting chickenpox, although you can get it as an adult, too.\r\n\r\nBefore the availability of the first vaccine against chickenpox in 1995, almost everyone got chickenpox as a toddler or young child. But since the late 1990s, the rate of chickenpox has declined by nearly 90%. Today, most children receive a vaccine against chickenpox as part of their routine immunization schedule.\r\n\r\nOnce you’ve had chickenpox, you won’t catch it again from another person. If you’re not vaccinated, you can get chickenpox at any age. Adults who get chickenpox may become very sick, so it’s better to have chickenpox when you’re a child or prevent getting it by receiving the vaccine.', 'The symptoms of chickenpox usually appear in a specific order, starting with a low-grade fever, followed by fatigue, headache, and stomach discomfort that may cause loss of appetite. Soon after, a red, itchy skin rash develops, initially appearing as small blisters filled with a milky liquid. These blisters eventually burst and form scabs, leading to blotchy, crusty spots across the skin. As the infection progresses, the scabs gradually dry out and fade away. The rash typically spreads across the face, chest, back, and limbs, causing intense itching and discomfort.', 'Chickenpox is caused by the varicella-zoster virus, a member of the herpesvirus family, which also causes shingles in adults. The virus spreads easily from person to person, starting 1 to 2 days before blisters appear until all the blisters have crusted over. Infection can occur through direct contact with fluid from blisters or by inhaling respiratory droplets when an infected person coughs or sneezes. Children under 10 years old are the most commonly affected, and the disease is usually mild in them. However, older children and adults tend to experience more severe symptoms, and in some cases, serious complications may arise.', '[\"https://www.youtube.com/embed/D-zaKmcXm8w?si=tp-Sv9N-w_f8Jnu_\",\"https://www.youtube.com/embed/mkMRuCzljlU?si=gG3ESeCetJVX67dZ\",\"https://www.youtube.com/embed/lxKINYL2Lxg?si=J-uuMXGhsM4PTNah\"]', '[\"cp1.jpg\",\"cp2.jpg\"]', 'Liver'),
(28, 'Depression', NULL, 'Depression is a mood disorder that causes a persistent feeling of sadness and loss of interest. Also called major depressive disorder or clinical depression, it affects how you feel, think and behave and can lead to a variety of emotional and physical problems. You may have trouble doing normal day-to-day activities, and sometimes you may feel as if life isn\'t worth living.\r\n\r\nMore than just a bout of the blues, depression isn\'t a weakness and you can\'t simply \"snap out\" of it. Depression may require long-term treatment. But don\'t get discouraged. Most people with depression feel better with medication, psychotherapy or both.', 'During these episodes, symptoms occur most of the day, nearly every day. Individuals may experience persistent feelings of sadness, emptiness, or hopelessness, along with irritability, frustration, and even angry outbursts over small matters. There is often a loss of interest or pleasure in normal activities, including hobbies, sports, or even intimate relationships. Sleep disturbances, such as insomnia or excessive sleeping, are common, along with persistent fatigue and low energy, making even simple tasks feel exhausting. Changes in appetite may lead to weight loss or gain, and individuals may experience heightened anxiety, restlessness, or slowed thinking and movements.', 'Depression is associated with various biological factors. People with depression often exhibit physical changes in their brains, though the exact significance of these changes remains uncertain. Brain chemistry also plays a crucial role, as neurotransmitters—naturally occurring chemicals in the brain—are believed to influence mood regulation. Changes in their function and interaction with neurocircuits may contribute to depression and its treatment. Hormonal imbalances can also be a trigger, occurring due to pregnancy, postpartum changes, thyroid disorders, menopause, or other medical conditions.', '[\"https://www.youtube.com/embed/BSi3d6iEEUc?si=phiIUm_litJ6mIjN\",\"https://www.youtube.com/embed/BZOLxSQwER8?si=8PEbOdgRQSoNvsms\",\"https://www.youtube.com/embed/LLxFu0g87ko?si=V9tP_fb4cM-DHSYy\"]', '[\"depression1.jpg\",\"depression.jpg\"]', 'Brain'),
(29, 'Dental abscess', NULL, 'A tooth abscess is a pocket of pus that\'s caused by a bacterial infection. The abscess can occur at different areas near the tooth for different reasons. A periapical (per-e-AP-ih-kul) abscess occurs at the tip of the root. A periodontal (per-e-o-DON-tul) abscess occurs in the gums at the side of a tooth root. The information here is about periapical abscesses.\r
\r
A periapical tooth abscess usually occurs as a result of an untreated dental cavity, an injury or prior dental work. The resulting infection with irritation and swelling (inflammation) can cause an abscess at the tip of the root.\r
\r
Dentists will treat a tooth abscess by draining it and getting rid of the infection. They may be able to save your tooth with a root canal treatment. But in some cases the tooth may need to be pulled. Leaving a tooth abscess untreated can lead to serious, even life-threatening, complications.\r
\r
', 'A tooth abscess often causes a severe, constant, throbbing toothache that can spread to the jawbone, neck, or ear. It may lead to pain or discomfort when exposed to hot or cold temperatures or when chewing and biting. Swelling in the face, cheek, or neck can occur, sometimes making it difficult to breathe or swallow. Fever and tender, swollen lymph nodes under the jaw or in the neck are common signs. A foul odor in the mouth may develop, and if the abscess ruptures, there can be a sudden rush of foul-smelling, salty fluid, bringing temporary pain relief.', 'A periapical tooth abscess occurs when bacteria invade the dental pulp. The pulp is the innermost part of the tooth that contains blood vessels, nerves and connective tissue.\r
\r
Bacteria enter through either a dental cavity or a chip or crack in the tooth and spread all the way down to the root. The bacterial infection can cause swelling and inflammation at the tip of the root.', '[\"https://www.youtube.com/embed/dMnne9FEfS4?si=pJ_LCi31zMOW_Y5d\",\"https://www.youtube.com/embed/SxmPHDSrKW8?si=Jdyzf7IZFCyw8Gak\",\"https://www.youtube.com/embed/88A4D4TyT-g?si=LgaKDwkDWzDABsXi\"]', '[\"dental.jpg\",\"dental1.jpg\"]', 'Teeth'),
(30, 'Ebola virus disease', NULL, 'Ebola is a type of viral hemorrhagic fever caused by several species of viruses from the genus Ebolavirus. Symptoms of Ebola start out flu-like but can progress to severe vomiting, bleeding and neurological (brain and nerve) issues.\r\n\r\nEbola can spread to people from bats, nonhuman primates and antelope. From there it can spread from human to human and cause outbreaks (where large numbers of people get infected around the same time). Outbreaks mostly happen in parts of Africa.\r\nEbola virus disease (EVD) is one of the diseases caused by ebolaviruses (specifically, Zaire ebolavirus) and known as “Ebola.” It’s the most common cause of Ebola outbreaks and deaths. Researchers have only tested the Ebola vaccine and treatments for efficacy against EVD, not other types of Ebola.', 'Ebola symptoms typically begin with a severe headache, muscle pain, and a sore throat, followed by extreme fatigue and weakness. Patients often experience loss of appetite, along with vomiting or diarrhea, which may contain blood. A rash or small spots of blood under the skin (petechiae or purpura) can also develop. As the disease progresses, individuals may suffer from unexplained bleeding or bruising. The combination of these symptoms leads to rapid deterioration, requiring immediate medical attention.', 'Ebola disease in humans is caused by four species of viruses: Zaire ebolavirus, Sudan ebolavirus, Taï Forest ebolavirus, and Bundibugyo ebolavirus. These viruses originate in bats, nonhuman primates such as monkeys and apes, and antelope found in West, Central, and East Africa. Despite being different species, all ebolaviruses cause similar symptoms and spread in the same manner. They are transmitted through direct contact with bodily fluids of infected individuals or animals. The disease is highly contagious and can lead to severe outbreaks, requiring strict containment measures.', '[\"https://www.youtube.com/embed/nvVgIsMCjJk?si=GTpc8v_fPZpL3pWZ\",\"https://www.youtube.com/embed/HCpaVanLomQ?si=oD9nAkOFG5cwZWOW\",\"https://www.youtube.com/embed/3JcA4jTNuIo?si=xX0alR2BvDd-ZWQB\"]', '[\"Ebola.jpg\",\"Ebola2.jpg\"]', 'Liver');
INSERT INTO `disease_information` (`disease_id`, `disease_name`, `disease_type`, `overview`, `symptoms`, `causes`, `videos`, `infographics`, `organ_name`) VALUES
(31, 'Flu', NULL, 'The flu is an illness you get from the influenza virus. It causes symptoms like head and body aches, sore throat, fever and respiratory symptoms, which can be severe. Flu is most common in winter months, when many people can get sick at once (an epidemic).\r\nFlu season — when cases of the flu go up dramatically — in the Northern Hemisphere (which includes the U.S.) is October through May. The highest number of cases (peak) usually happen between December and February.\r\nThe flu is one of the most common infectious diseases. Every flu season, about 20 to 40 million people in the U.S. catch the flu.\r\nThe flu and the common cold can have similar symptoms, like runny nose and cough. But cold symptoms are usually mild and flu symptoms can be severe and lead to serious complications. Different viruses cause colds and the flu.', 'Flu symptoms usually appear suddenly and may include fever, chills, and body aches, making you feel weak and exhausted. A persistent cough, headache, and sore throat are common, along with nasal congestion that leads to a runny or stuffy nose. Many people experience extreme tiredness or a general feeling of being run down. In some cases, especially in children, flu symptoms can also include diarrhea or vomiting. While not everyone experiences all these symptoms, the flu can cause significant discomfort and fatigue.', 'The influenza virus causes flu. Influenza A, B and C are the most common types that infect people. Influenza A and B are seasonal (most people get them in the winter) and have more severe symptoms. Influenza C doesn’t cause severe symptoms and it’s not seasonal — the number of cases stays about the same throughout the year.\r\n\r\nH1N1 (“swine flu”) and bird flu are both subtypes of influenza A.', '[\"https://www.youtube.com/embed/jYhnlqE1cJY?si=cZXTzGyWl6aniLbW\",\"https://www.youtube.com/embed/lLaPKP96jXA?si=jHhWLvJe-u1i9n-U\",\"https://www.youtube.com/embed/BGTsyYQq0xs?si=sW8ySXuQUGSROscC\"]', '[\"flu.jpg\",\"flu1.jpg\"]', 'Nose'),
(32, 'Food poisoning', NULL, 'Food poisoning, a type of foodborne illness, is a sickness people get from something they ate or drank. The causes are germs or other harmful things in the food or beverage.\r\n\r\nSymptoms of food poisoning often include upset stomach, diarrhea and vomiting. Symptoms usually start within hours or several days of eating the food. Most people have mild illness and get better without treatment.\r\n\r\nSometimes food poisoning causes severe illness or complications.', 'Symptoms vary depending on the cause of the illness and can start within a few hours or take several weeks to appear. Common symptoms include an upset stomach, vomiting, and diarrhea, which may sometimes contain blood. Stomach pain and cramps can occur, often accompanied by fever and headache. The severity of symptoms can range from mild discomfort to more intense pain and dehydration. In some cases, symptoms may persist for an extended period, requiring medical attention.', 'Many germs and harmful substances, known as contaminants, can cause foodborne illnesses. When food or drink carries a contaminant, it is considered \"contaminated.\" Contamination can occur due to bacteria, viruses, or parasites that live in the intestines. Some bacteria and molds produce toxins that can also lead to illness. Ingesting these harmful substances can cause various health issues, ranging from mild discomfort to severe infections. Proper food handling, cooking, and storage are essential to reducing the risk of contamination.', '[\"https://www.youtube.com/embed/Pq2me3r0cz4?si=iMsfG8JRYhAtSTsC\",\"https://www.youtube.com/embed/BCRW2o15qsQ?si=LrBwBosDN6PWju7I\",\"https://www.youtube.com/embed/CvInSmGaAu4?si=0Z5q5wYPhCQGWkdr\"]', '[\"food_p.jpg\",\"food_p1.jpg\"]', 'Stomach');

-- --------------------------------------------------------

--
-- Table structure for table `doctor`
--

CREATE TABLE `doctor` (
  `d_id` int(11) NOT NULL,
  `f_name` varchar(255) NOT NULL,
  `l_name` varchar(255) NOT NULL,
  `dob` date NOT NULL,
  `gender` varchar(10) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `experience` int(11) NOT NULL,
  `specialization` varchar(255) NOT NULL,
  `consultation_fees` varchar(255) NOT NULL,
  `contact_no` bigint(10) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `state` varchar(255) NOT NULL,
  `country` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `certificate_path` varchar(255) DEFAULT NULL,
  `approval` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor`
--

INSERT INTO `doctor` (`d_id`, `f_name`, `l_name`, `dob`, `gender`, `doctor_id`, `experience`, `specialization`, `consultation_fees`, `contact_no`, `address`, `city`, `state`, `country`, `email`, `pass`, `certificate_path`, `approval`) VALUES
(1, 'vidit\r\n', 'jani', '2025-01-02', 'male', 1, 11, 'surgeon', '300', 888888888, 'c/1 sardarkunj society', 'ahmedabad', 'gujarat', 'india', 'alpesh.food@gmail.com', '12345', NULL, ''),
(2, 'Keni\r\n', 'Patel', '2025-01-02', 'female', 1, 17, 'Dentist', '500', 888888888, 'c/1 sardarkunj society', 'ahmedabad', 'gujarat', 'india', 'alpesh.food@gmail.com', '12345', NULL, ''),
(3, 'Keval', 'Bhavsar', '2005-03-18', 'male', 3, 10, 'Neurosurgeon', '1000', 8799662611, 'surat', 'surat', 'gujarat', 'india', 'abc@gmail.com', '12345', NULL, ''),
(4, 'Aditi', 'Kanojiya', '2004-11-26', 'female', 4, 8, 'cardiologist', '4000', 8799332611, 'c/1 sardarkunj society', 'ahmedabad', 'gujarat', 'india', 'alpesh.food@gmail.com', '12345', NULL, ''),
(5, 'Vidit', 'Jani', '2004-07-13', 'Male', 738335656, 10, 'Cardio', '5000', 738335656, 'sufiji street', 'SURAT CITY', 'Gujarat', 'India', '18kevalbhavsar@gmail.com', '$2y$10$np52naIW4rD4GlLflfJ9lef.fraUtmdXLInvQ3kaLWJQKXjbNYDPi', 'Applcation_Final(Keval).pdf', ''),
(12, 'Jaswant', 'Dave', '2005-12-12', 'Male', 123457890, 21, 'Ophthalmologist', '11', 1234567890, 'ahmedabad', 'ahmedabad', 'Gujarat', 'India', 'jassu@gmail.com', '$2y$10$VuKzGJtZpJRcISAVfQzaAO5.49FTC3b5pxcI06XK1pbXyCpeS8if6', '8728427574.pdf', 'pending'),
(14, 'ramesh', 'kumar', '2222-02-22', 'Male', 13675658, 11, 'DERMAT', '1000', 7383312206, 'Althan', 'SURAT CITY', 'Gujarat', 'India', 'vidit.jani.13@gmail.com', '$2y$10$5pe8A5ge4ohl2RFRTL2yG.x/X6gkT1ms6ZK9Oi3EZpeArlM0JNnou', 'INTERSTELLER.jpg', 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `likes`
--

CREATE TABLE `likes` (
  `like_id` int(11) NOT NULL,
  `post_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medications`
--

CREATE TABLE `medications` (
  `drug_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `brand_name` varchar(255) NOT NULL,
  `generic_name` varchar(255) NOT NULL,
  `strength` varchar(100) NOT NULL,
  `form` varchar(100) NOT NULL,
  `duration` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medications`
--

INSERT INTO `medications` (`drug_id`, `user_id`, `brand_name`, `generic_name`, `strength`, `form`, `duration`, `created_at`) VALUES
(1, 4, 'Apo-Ciproflix', 'Ciprofloxacin Hydrochloride', '250mg', 'Tab', '7 days', '2025-02-13 21:22:27'),
(2, 4, 'assd', 'asdasa', 'sdasa', 'sdsa', 'asda', '2025-02-13 22:07:39'),
(3, 4, 'sdcsaa', 'sdasa', 'sdsad', 'asdaasda', 'adssa', '2025-02-13 22:09:37'),
(4, 4, 'cscsdc', 'csd', 'scds', 'scdsc', 'scdds', '2025-02-13 22:15:46'),
(5, 4, 'saca', 'csaa', 'scaacs', 'acs', 'acsa', '2025-02-13 22:17:38'),
(6, 4, 'dcs', 'cdsdc', 'dcssd', 'scds', 'cdssc', '2025-02-13 22:22:09'),
(7, 4, 'sdcsd', 'dsc', 'scd', 'cdscd', 'cds', '2025-02-13 22:22:28'),
(8, 4, 'asca', 'sdc', 'scd', 'scd', 'sdc', '2025-02-13 22:23:05'),
(9, 4, 'keval', 'keval', 'keval', 'keval', 'keval', '2025-02-13 22:24:10'),
(10, 5, 'aditi', 'aditi', 'aditi', 'aditi', 'aditi', '2025-02-13 22:26:30'),
(11, 15, 'aditi', 'aditi', 'aditi', 'aditi', 'aditi', '2025-02-15 13:00:14');

-- --------------------------------------------------------

--
-- Table structure for table `patient`
--

CREATE TABLE `patient` (
  `pid` int(11) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `dob` date NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `height` int(11) NOT NULL,
  `weight` int(11) NOT NULL,
  `bloodgroup` varchar(255) NOT NULL,
  `contact_no` varchar(15) NOT NULL,
  `state` varchar(50) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient`
--

INSERT INTO `patient` (`pid`, `firstname`, `lastname`, `dob`, `gender`, `height`, `weight`, `bloodgroup`, `contact_no`, `state`, `country`, `email`, `password`) VALUES
(1, 'Keval', 'Bhavsar', '2005-03-18', 'Male', 165, 65, 'O+', '9316973144', 'Gujarat', 'India', '18kevalbhavsar@gmail.com', '$2y$10$lisIRuiZrLZcfc5WEpzzhebWcBj7b7v99GRgekfb8Ale.5I.qC3UO'),
(2, 'aditi', 'kanojiya', '2004-11-26', 'Female', 160, 67, '0', '8799332611', 'GUJARAT', 'India', 'alpesh.food@gmail.com', '$2y$10$4Xz8Ve3IQUTuzjWu8L6hAOyUwIszkgbiDcH40pDrjjeynG9i1rQoe'),
(3, 'Vidit', 'Jani', '2004-07-13', 'Male', 184, 85, '0', '9316973144', 'Gujarat', 'India', 'vidit@gmail.com', '$2y$10$EijQfamdmpKIdK1N.BzML.jEH5GRYb3y2yF1IYxP2LjbHoFCn75Oq'),
(6, 'Aditi', 'Kanojiya', '2004-11-26', 'Female', 152, 66, '0', '8799332611', 'Gujarat', 'India', 'kanojiyaaditi26@gmail.com', '$2y$10$JSLZwwhuggKvbReBv9a4autx3e01rs83j.9g76ukaXIcQbSB74bVi');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `post_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `community_id` int(11) DEFAULT NULL,
  `fname` varchar(255) NOT NULL,
  `lname` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `media_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `report_type` varchar(255) DEFAULT NULL,
  `referred_by` varchar(255) DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `user_id`, `report_type`, `referred_by`, `report_date`, `file_path`) VALUES
(3, 4, 'Sugar Report', 'Dipak Patel', '2019-12-25', '4_18kevalbhavsar@gmail.com/8728427574.pdf'),
(4, 4, 'Cholesterol Report', 'Vikash gupta', '2023-04-22', '4_18kevalbhavsar@gmail.com/Health-Hack-Flyer-3.pdf'),
(5, 4, 'Blood Report', 'Vikash gupta', '2025-02-18', '4_18kevalbhavsar@gmail.com/Applcation_Final(Keval).pdf'),
(6, 4, 'Blood Report', 'Ankitgupta', '2025-02-24', '4_18kevalbhavsar@gmail.com/230170116012.pdf'),
(7, 5, 'Sugar Report', 'Dipak Patel', '2025-02-04', '../../uploads/Patient_Reports/5_alpesh.food@gmail.com/230170116012.pdf'),
(8, 5, 'Sugar Report', 'jcsvahj', '2025-02-03', '../../uploads/Patient_Reports/5_alpesh.food@gmail.com/230170116012.pdf'),
(9, 5, 'Sugar Report', 'afsf', '2025-02-20', '../../uploads/Patient_Reports/5_alpesh.food@gmail.com/formulation and evaluation of multipurpose herbal cream.pdf'),
(10, 4, 'Blood Report', 'kjsjdfbsd', '2025-02-03', '4_18kevalbhavsar@gmail.com/Screenshot (1).png'),
(11, 15, 'Blood Report', 'vidit', '2025-02-15', '15_kanojiyaaditi26@gmail.com/Screenshot (57).png');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `fname` varchar(255) NOT NULL,
  `lname` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `fname`, `lname`, `password`, `user_type`, `created_at`) VALUES
(4, '18kevalbhavsar@gmail.com', 'Keval', 'Bhavsar', '$2y$10$lisIRuiZrLZcfc5WEpzzhebWcBj7b7v99GRgekfb8Ale.5I.qC3UO', 'patient', '2025-01-30 18:53:58'),
(5, 'alpesh.food@gmail.com', '', '', '$2y$10$4Xz8Ve3IQUTuzjWu8L6hAOyUwIszkgbiDcH40pDrjjeynG9i1rQoe', 'patient', '2025-02-03 09:54:46'),
(6, 'admin@gmail.com', '', '', '$2y$10$NQI2g6c4Xf1MVs7Eu6cJMO8fgZ7n2vjmfgkdQoZJjc3lO5PC.BzFq', 'admin', '2025-02-05 20:02:12'),
(8, 'vidit@gmail.com', 'Vidit', 'Jani', '$2y$10$n.C../FY3GBK9ZbVWJnTLuQJ.4OOjG8dR.OaMECz5rXDEzitb0rJy', 'patient', '2025-02-11 13:11:30'),
(10, 'abc@gmail.com', 'Vidit', 'Jani', '$2y$10$N39FmxXYSA4lR672TSWl4uO7EXczxLGTt/ZbFbtzk2mTPw52PTDNy', 'doctor', '2025-02-13 09:56:38'),
(11, 'jassu@gmail.com', 'Jaswant', 'Dave', '$2y$10$VuKzGJtZpJRcISAVfQzaAO5.49FTC3b5pxcI06XK1pbXyCpeS8if6', 'doctor', '2025-02-13 10:24:13'),
(12, 'admin123@gmail.com', '', '', '$2y$10$xJDhkyUV1kZURtJEySBbk.ZPktAQsjW4BpDFGHZMkGA4PZIttsSFK', 'admin', '2025-02-14 20:25:38'),
(13, 'xyz@gmail.com', 'dscsd', 'scs', '$2y$10$rLZ/AEIY9fPj3/d19uJlM.tMKahBCMIy8N7CHd8QVQx2gVo.AuUQ6', 'doctor', '2025-02-15 07:34:05'),
(14, 'vidit.jani.13@gmail.com', 'ramesh', 'kumar', '$2y$10$5pe8A5ge4ohl2RFRTL2yG.x/X6gkT1ms6ZK9Oi3EZpeArlM0JNnou', 'doctor', '2025-02-15 09:50:02'),
(15, 'kanojiyaaditi26@gmail.com', 'Aditi', 'Kanojiya', '$2y$10$JSLZwwhuggKvbReBv9a4autx3e01rs83j.9g76ukaXIcQbSB74bVi', 'patient', '2025-02-15 12:53:15');

-- --------------------------------------------------------

--
-- Table structure for table `visit_counter`
--

CREATE TABLE `visit_counter` (
  `id` int(11) NOT NULL,
  `counter` int(255) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `visit_counter`
--

INSERT INTO `visit_counter` (`id`, `counter`) VALUES
(1, 691);

-- --------------------------------------------------------

--
-- Table structure for table `weight_tracker`
--

CREATE TABLE `weight_tracker` (
  `user_id` int(11) NOT NULL,
  `month` date NOT NULL,
  `weight` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `weight_tracker`
--

INSERT INTO `weight_tracker` (`user_id`, `month`, `weight`, `created_at`) VALUES
(4, '2025-02-15', 65, '2025-02-15 15:01:45'),
(4, '2025-02-15', 85, '2025-02-15 15:01:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointment`
--
ALTER TABLE `appointment`
  ADD PRIMARY KEY (`appointment_id`),
  ADD UNIQUE KEY `did` (`did`,`appointment_date`,`appointment_time`),
  ADD KEY `pid` (`pid`),
  ADD KEY `fk_user` (`user_id`);

--
-- Indexes for table `biomarker`
--
ALTER TABLE `biomarker`
  ADD PRIMARY KEY (`data_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `communities`
--
ALTER TABLE `communities`
  ADD PRIMARY KEY (`community_id`),
  ADD KEY `communities_ibfk_1` (`created_by`);

--
-- Indexes for table `communityreports`
--
ALTER TABLE `communityreports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `disease_information`
--
ALTER TABLE `disease_information`
  ADD PRIMARY KEY (`disease_id`);

--
-- Indexes for table `doctor`
--
ALTER TABLE `doctor`
  ADD PRIMARY KEY (`d_id`);

--
-- Indexes for table `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`like_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `medications`
--
ALTER TABLE `medications`
  ADD PRIMARY KEY (`drug_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `patient`
--
ALTER TABLE `patient`
  ADD PRIMARY KEY (`pid`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `community_id` (`community_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `visit_counter`
--
ALTER TABLE `visit_counter`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointment`
--
ALTER TABLE `appointment`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `biomarker`
--
ALTER TABLE `biomarker`
  MODIFY `data_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `communities`
--
ALTER TABLE `communities`
  MODIFY `community_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `communityreports`
--
ALTER TABLE `communityreports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `disease_information`
--
ALTER TABLE `disease_information`
  MODIFY `disease_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `doctor`
--
ALTER TABLE `doctor`
  MODIFY `d_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `likes`
--
ALTER TABLE `likes`
  MODIFY `like_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medications`
--
ALTER TABLE `medications`
  MODIFY `drug_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `patient`
--
ALTER TABLE `patient`
  MODIFY `pid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `visit_counter`
--
ALTER TABLE `visit_counter`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointment`
--
ALTER TABLE `appointment`
  ADD CONSTRAINT `appointment_ibfk_1` FOREIGN KEY (`pid`) REFERENCES `patient` (`pid`),
  ADD CONSTRAINT `appointment_ibfk_2` FOREIGN KEY (`did`) REFERENCES `doctor` (`d_id`),
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `biomarker`
--
ALTER TABLE `biomarker`
  ADD CONSTRAINT `biomarker_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `communities`
--
ALTER TABLE `communities`
  ADD CONSTRAINT `communities_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `communityreports`
--
ALTER TABLE `communityreports`
  ADD CONSTRAINT `communityreports_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`),
  ADD CONSTRAINT `communityreports_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`),
  ADD CONSTRAINT `likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `medications`
--
ALTER TABLE `medications`
  ADD CONSTRAINT `medications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`community_id`) REFERENCES `communities` (`community_id`);

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Table structure for table `hospital_info`
--

CREATE TABLE `hospital_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hospital_name` varchar(255) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `google_maps_link` varchar(500) DEFAULT NULL,
  `emergency_helpline` varchar(20) DEFAULT NULL,
  `hospital_timings` varchar(100) DEFAULT NULL,
  `opd_timings` varchar(100) DEFAULT NULL,
  `specialties` text,
  `facilities` text,
  `accreditations` text,
  `insurance_accepted` text,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `doctor_schedule`
--

CREATE TABLE `doctor_schedule` (
  `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `day_of_week` varchar(10) NOT NULL,
  `available_from` time NOT NULL,
  `available_to` time NOT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`schedule_id`),
  FOREIGN KEY (`doctor_id`) REFERENCES `doctor`(`d_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Insert default hospital information
--
INSERT INTO `hospital_info` (`hospital_name`, `phone_number`, `address`, `google_maps_link`, `emergency_helpline`, `hospital_timings`, `opd_timings`, `specialties`) VALUES
('MedC Hospital', '+91-9876543210', '123 Healthcare Avenue, Medical City, India', 'https://maps.google.com/?q=hospital', '+91-9876543211', '24x7', 'Mon-Sat: 8AM-8PM', 'Cardiology,Orthopedics,Pediatrics,Dermatology,Ophthalmology');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
