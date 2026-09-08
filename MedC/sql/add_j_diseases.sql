INSERT INTO disease_information (disease_name, overview, symptoms, causes, videos, infographics, organ_name)
SELECT
    'Jaundice',
    'Jaundice is a condition in which the skin, the whites of the eyes, and body fluids turn yellow because of a buildup of bilirubin in the blood. It is often a sign of an underlying problem involving the liver, gallbladder, bile ducts, or red blood cells rather than a disease on its own. The severity depends on the cause and how quickly it is treated.',
    'The most obvious sign is yellowing of the eyes and skin. Many people also notice dark urine, pale stools, itching, tiredness, nausea, poor appetite, or discomfort in the upper abdomen. In some cases, fever or weight loss may appear when the jaundice is related to infection or blockage.',
    'Jaundice happens when bilirubin is not processed or removed normally. Common causes include hepatitis, liver damage, gallstones, blockage of bile ducts, certain blood disorders, or problems with the pancreas. In newborns, jaundice can happen because the liver is still maturing.',
    '["https://www.youtube.com/embed/47ul3Xj8AI4","https://www.youtube.com/embed/gLVf9x2coUc","https://www.youtube.com/embed/IkTPnzbTg88?si=Nam-vFHkN_pi_UEF"]',
    '["overview.svg"]',
    'Liver'
WHERE NOT EXISTS (
    SELECT 1 FROM disease_information WHERE disease_name = 'Jaundice'
);

INSERT INTO disease_information (disease_name, overview, symptoms, causes, videos, infographics, organ_name)
SELECT
    'Japanese Encephalitis',
    'Japanese encephalitis is a mosquito-borne viral infection that can inflame the brain. Most infected people have no symptoms or only mild illness, but a small number develop serious neurological disease. It is more common in parts of Asia and tends to occur in rural or agricultural areas where mosquitoes breed near animals and standing water.',
    'When symptoms occur, they may start with fever, headache, vomiting, and weakness. Severe cases can progress to neck stiffness, confusion, seizures, movement problems, and reduced consciousness. Recovery can be slow, and some patients are left with long-term neurological complications.',
    'The condition is caused by the Japanese encephalitis virus and spreads through bites from infected mosquitoes. Pigs and water birds act as amplifying hosts, while humans are usually accidental hosts. The risk is higher in areas with stagnant water, mosquito exposure, and low vaccination coverage.',
    '["https://www.youtube.com/embed/m2U1ERuhqWM","https://www.youtube.com/embed/hFuDs74NPc4?si=5FRMwL5LpJ9wuArT","https://www.youtube.com/embed/P-_hqG9HKj8?si=br_wU8w6ViXwJDFp"]',
    '["overview.svg"]',
    'Brain'
WHERE NOT EXISTS (
    SELECT 1 FROM disease_information WHERE disease_name = 'Japanese Encephalitis'
);

INSERT INTO disease_information (disease_name, overview, symptoms, causes, videos, infographics, organ_name)
SELECT
    'Jejunitis',
    'Jejunitis refers to inflammation of the jejunum, which is the middle section of the small intestine. It can appear as part of a digestive infection, irritation, autoimmune disorder, or inflammatory bowel condition. Because the jejunum plays an important role in nutrient absorption, ongoing inflammation may lead to weakness and digestive discomfort.',
    'Symptoms may include cramp-like abdominal pain, bloating, nausea, vomiting, diarrhea, and poor appetite. Some people develop tiredness, dehydration, or weight loss when symptoms are severe or persistent. The discomfort is often felt around the central abdomen.',
    'Jejunitis may be caused by viral or bacterial infections, food contamination, inflammatory bowel disease, irritation from certain medicines, or reduced blood supply to part of the intestine. Sometimes it develops alongside broader digestive inflammation involving nearby parts of the bowel.',
    '["https://www.youtube.com/embed/8O0BkAGaOHY","https://www.youtube.com/embed/Pq2me3r0cz4?si=iMsfG8JRYhAtSTsC","https://www.youtube.com/embed/BCRW2o15qsQ?si=LrBwBosDN6PWju7I"]',
    '["overview.svg"]',
    'intestine'
WHERE NOT EXISTS (
    SELECT 1 FROM disease_information WHERE disease_name = 'Jejunitis'
);

INSERT INTO disease_information (disease_name, overview, symptoms, causes, videos, infographics, organ_name)
SELECT
    'Jackhammer Esophagus',
    'Jackhammer esophagus is a swallowing disorder in which the muscles of the esophagus contract with unusually great force. These contractions may cause intense chest pain or make swallowing uncomfortable. Although food may still pass, the movement can feel abnormal and distressing, especially during meals.',
    'People may experience chest pain, painful swallowing, a sensation that food is stuck, difficulty swallowing, or regurgitation. Episodes may come and go, and some patients confuse the pain with heart-related pain because it can be severe and sudden.',
    'The exact cause is not always clear, but it is linked to abnormal nerve control of the esophageal muscles. It may occur together with acid reflux, stress, or other motility disorders. Certain foods or very hot or cold drinks may trigger symptoms in some people.',
    '["https://www.youtube.com/embed/8MctTy4LWj8"]',
    '["overview.svg"]',
    'Throat'
WHERE NOT EXISTS (
    SELECT 1 FROM disease_information WHERE disease_name = 'Jackhammer Esophagus'
);

INSERT INTO disease_information (disease_name, overview, symptoms, causes, videos, infographics, organ_name)
SELECT
    'Juvenile Macular Degeneration',
    'Juvenile macular degeneration is a group of inherited eye conditions that affect the macula, the central part of the retina responsible for sharp detailed vision. It usually begins in childhood, adolescence, or early adulthood and can make reading, recognizing faces, and seeing fine details difficult while side vision is often preserved.',
    'Common signs include blurred or distorted central vision, trouble reading small print, difficulty seeing faces clearly, increased sensitivity to light, and slower adjustment to dim environments. Vision changes may progress gradually over time.',
    'This condition is usually caused by inherited genetic changes that affect retinal function. Different forms exist, such as Stargardt disease and Best disease. Because it is genetic, it can run in families and may appear even without obvious external risk factors.',
    '["https://www.youtube.com/embed/xu9aTFc3DrM","https://www.youtube.com/embed/4DR5o6FAz5I?si=r7175LLDrFNdA_fL","https://www.youtube.com/embed/jQi-EYHfNuw?si=vzTIaitk1TBwHK1N"]',
    '["overview.svg"]',
    'Eye'
WHERE NOT EXISTS (
    SELECT 1 FROM disease_information WHERE disease_name = 'Juvenile Macular Degeneration'
);
